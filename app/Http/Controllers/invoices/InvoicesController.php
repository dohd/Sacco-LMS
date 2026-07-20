<?php

namespace App\Http\Controllers\invoices;

use App\Http\Controllers\Controller;
use App\Http\Services\ZohoService;
use App\Models\invoice\Invoice;
use App\Models\invoice\InvoiceItem;
use App\Models\stockadj\Stockadj;
use App\Models\stockadj\StockadjItem;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InvoicesController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new ZohoService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('invoices.index');
    }

    public function datatable()
    {
        $invoices = Invoice::whereNull('deleted_at')
            ->latest()
            ->limit(request('limit'))
            ->offset(request('offset'))
            ->get();

        return view('invoices.partial.datatable', compact('invoices'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        $excluded = [
            'id', 'ins', 'user_id', 'created_at', 'updated_at',
        ];
        $employeeCols = DB::table('information_schema.columns')
        ->select('COLUMN_NAME')
        ->where('table_schema', DB::getDatabaseName()) // current DB
        ->where('table_name', 'invoices')
        ->whereNotIn('COLUMN_NAME', $excluded)
        ->orderBy('ORDINAL_POSITION')
        ->pluck('COLUMN_NAME')
        ->toArray();
        $employeeCols = array_chunk($employeeCols, 4);

        return view('invoices.create', compact('employeeCols'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'date' => 'required',
            'due_date' => 'required',
            'location_id' => 'required',
        ], [
            'customer_id.required' => 'Customer is required',
            'date.required' => 'Invoice date is required',
            'date.date' => 'Invoice date must be a valid date',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after_or_equal' => 'Due date must be after or equal to the invoice date',
            'location_id.required' => 'Location is required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors(); // This is a MessageBag
            // Get all errors as array
            $errorMessages = $errors->all();
            // Get specific field errors
            // $customerErrors = $errors->get('customer_id');
            return response()->json([
                'status' => 'error', 
                'message' => 'Validation failed! ' . implode(', ', $errorMessages),
                'errors' => $errors
            ], 422);
        }

        // extract input
        $dataItems = $request->only([
            'item_id', 'row_index', 'name', 'description', 'unit', 
            'quantity', 'rate', 'amount', 'tax_id', 'tax_percentage',
            'item_tax', 'product_type', 'sku'
        ]);
        $data = $request->except(['_token', ...array_keys($dataItems)]);

        $zohoInvoice = null;
        $zohoAdjs = [];
        $adjResponses = [];
        try {
            // sanitize
            foreach ($data as $key => $value) {
                if (in_array($key, ['date', 'due_date'])) $data[$key] = databaseDate($value);
                if (in_array($key, ['taxable', 'tax', 'subtotal', 'total'])) {
                    $data[$key] = numberClean($value);
                }
            }
            foreach ($dataItems as $key => $value) {
                if (in_array($key, ['row_index', 'amount', 'item_tax', 'quantity', 'rate', 'tax_percentage'])) {
                    $dataItems[$key] = array_map(fn($v) => numberClean($v), $value);
                }
            }

            DB::beginTransaction();

            // create local invoice
            $invoice = Invoice::create($data);

            // create local invoice items
            $dataItems['invoice_id'] = array_fill(0, count($dataItems['name']), @$invoice->id);
            $dataItems['user_id'] = array_fill(0, count($dataItems['name']), @$invoice->user_id);
            $dataItems = databaseArray($dataItems);
            $dataItems = array_filter($dataItems, fn($v) => $v['name']);
            if (!$dataItems) throw new Exception('Invoice order items are required');
            InvoiceItem::insert($dataItems);

            // Post zoho invoice
            $adjResponses = [];
            $invResponse = $this->service->postInvoice($invoice);
            $zohoInvoice = @$invResponse->invoice; 
            $invItems = @$invResponse->invoice->line_items;
            if ($invItems && count($invItems)) {
                Log::info('Invoice Items: ' . strval(count($invItems)));
                foreach ($invItems as $invItem) {
                    $itemResp = $this->service->getItem($invItem->item_id);
                    $stockItem = @$itemResp->item;

                    if ($stockItem && $stockItem->product_type == 'service') {
                        $itemName = $stockItem->name;
                        // fetch composite items with replica name
                        $comItemResponse = $this->service->getCompositeItems(['name_contains' => $itemName]);
                        $comItems = @$comItemResponse->composite_items;
                        Log::info('Composite Items: ' . strval(count($comItems)));

                        if ($comItems && count($comItems)) {
                            $comItem = $comItems[0];
                            // fetch specific composite item 
                            $comItemResponse1 = $this->service->getCompositeItem($comItem->composite_item_id);
                            $mappedItems = @$comItemResponse1->composite_item->mapped_items;
                            Log::info('Mapped Composite Items: ' . strval(count($mappedItems)));

                            // post inventory adjustments for mapped items
                            if ($mappedItems && count($mappedItems)) {
                                $adjustmentLines = [];
                                foreach ($mappedItems as $mappedItem) {
                                    if ($mappedItem->product_type == 'goods') {
                                        $adjustmentLines[] = [
                                            "item_id" => $mappedItem->item_id,
                                            "quantity_adjusted" => -$mappedItem->quantity*$invItem->quantity
                                        ];
                                    }
                                }
                                Log::info('Adjustment Lines: ' . strval(count($adjustmentLines)));
                                $adjResponses[] = $this->service->postInventoryAdjustment([
                                  "reason" => "Inventory Revaluation",
                                  "description" => "Sales Invoice {$zohoInvoice->invoice_number}",
                                  "adjustment_type" => "quantity",
                                  "date" => $zohoInvoice->date,
                                  "location_id" => (string) (@$zohoInvoice->location_id ?: @$zohoInvoice->warehouse_id), // dynamic
                                  "line_items" => $adjustmentLines,
                                  "status" => 'draft',
                                ]);                            
                            }
                        }
                    }
                }
            }
            $invoice->update([
                'zoho_invoice_id' => $zohoInvoice->invoice_id,
                'zoho_invoice_number' => $zohoInvoice->invoice_number,
                'zoho_status' => $zohoInvoice->status,
                'zoho_exchange_rate' => $zohoInvoice->exchange_rate,
                'zoho_invoice_url' => $zohoInvoice->invoice_url,
            ]);

            // create local stock adjustment
            foreach ($adjResponses as $adjResponse) {
                $zohoAdj = @$adjResponse->inventory_adjustment;
                if ($zohoAdj) {
                    $zohoAdjs[] = $zohoAdj;
                    $stockAdj = Stockadj::create([
                        'reason' => $zohoAdj->reason,
                        'description' => $zohoAdj->description,
                        'adjustment_type' => $zohoAdj->adjustment_type,
                        'date' => $zohoAdj->date,
                        'location_id' => (string) (@$zohoAdj->location_id ?: @$zohoAdj->warehouse_id),
                        'invoice_id' => $invoice->id,
                        'zoho_inventory_adjustment_id' => $zohoAdj->inventory_adjustment_id,
                    ]);
                    foreach ($zohoAdj->line_items as $item) {
                        StockadjItem::create([
                            'stock_adj_id' => $stockAdj->id,
                            'item_id' => $item->item_id,
                            'quantity_adjusted' => $item->quantity_adjusted,
                            'zoho_line_item_id' => $item->line_item_id,
                            'zoho_item_name' => $item->name,
                            'zoho_adjustment_account_id' => $item->adjustment_account_id,
                            'zoho_adjustment_account_name' => $item->adjustment_account_name,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success', 
                'message' => 'Invoice created successfully',
                'redirectTo' => route('invoices.index'),
            ]);
        } catch (Exception $e) {
            $msg = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            Log::error($msg);
            $msg = $e->getMessage();

            // Clear Zoho Entries
            foreach ($zohoAdjs as $zohoAdj) {
                $this->service->deleteInventoryAdjustment($zohoAdj->inventory_adjustment_id);
                Log::info('Zoho Adjustment Cleared: ' . $zohoAdj->inventory_adjustment_id);
            }
            if ($zohoInvoice) {
                $this->service->deleteInvoice($zohoInvoice->invoice_id);
                Log::info('Zoho Invoice Cleared: ' . $zohoInvoice->invoice_id);
            }
            
            // Capture Zoho Error
            if ($e instanceof RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = (string) $response->getBody();
                // Try to decode JSON error message
                $error = json_decode($errorBody, true);
                $msg = "Zoho Error: " . ($error['message'] ?? $errorBody);
                Log::error($msg);
            } 
            
            return response()->json([
                'status' => 'error', 
                'message' => 'Invoice creation failed! ' . $msg,
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        return view('invoices.view', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Invoice $invoice, Request $request)
    { 
        // validate request
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'date' => 'required',
            'due_date' => 'required',
            'location_id' => 'required',
        ],[
            'customer_id.required' => 'Customer is required',
            'date.required' => 'Invoice date is required',
            'date.date' => 'Invoice date must be a valid date',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'due_date.after_or_equal' => 'Due date must be after or equal to the invoice date',
            'location_id.required' => 'Location is required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors(); // This is a MessageBag
            // Get all errors as array
            $errorMessages = $errors->all();
            // Get specific field errors
            // $customerErrors = $errors->get('customer_id');
            return response()->json([
                'status' => 'error', 
                'message' => 'Validation failed! ' . implode(', ', $errorMessages),
                'errors' => $errors
            ], 422);
        }

        // extract input
        $dataItems = $request->only([
            'item_id', 'row_index', 'name', 'description', 'unit', 
            'quantity', 'rate', 'amount', 'tax_id', 'tax_percentage',
            'item_tax', 'product_type', 'sku'
        ]);
        $data = $request->except(['_token', ...array_keys($dataItems)]);

        $zohoInvoice = null;
        $zohoAdjs = [];
        $adjResponses = [];
        try {
            // sanitize
            foreach ($data as $key => $value) {
                if (in_array($key, ['date', 'due_date'])) $data[$key] = databaseDate($value);
                if (in_array($key, ['taxable', 'tax', 'subtotal', 'total'])) {
                    $data[$key] = numberClean($value);
                }
            }
            foreach ($dataItems as $key => $value) {
                if (in_array($key, ['row_index', 'amount', 'item_tax', 'quantity', 'rate', 'tax_percentage'])) {
                    $dataItems[$key] = array_map(fn($v) => numberClean($v), $value);
                }
            }

            // Backup Remote Data
            $result = $this->backupRemoteData($invoice);
            if ($result !== true) return $result;

            DB::beginTransaction();

            // update local invoice
            $data['updated_by'] = auth()->user()->id;
            $result = $invoice->update($data);
            
            // update local invoice items
            $dataItems['invoice_id'] = array_fill(0, count($dataItems['name']), @$invoice->id);
            $dataItems['user_id'] = array_fill(0, count($dataItems['name']), @$invoice->user_id);
            $dataItems = databaseArray($dataItems);
            $dataItems = array_filter($dataItems, fn($v) => $v['name']);
            if (!$dataItems) throw new Exception('Invoice order items are required');
            $invoice->items()->delete();
            $invoice->items()->insert($dataItems);

            // clear previous Zoho inventory adjustments
            $adjustmentId = @$invoice->stockAdj->zoho_inventory_adjustment_id;
            if ($adjustmentId) $this->service->deleteInventoryAdjustment($adjustmentId);
            $invoice->stockAdj()->delete(); 

            // update Zoho invoice
            $adjResponses = [];
            $invResponse = $this->service->updateInvoice($invoice);
            $zohoInvoice = @$invResponse->invoice; 

            // post adjustments from invoice items
            $invItems = @$invResponse->invoice->line_items;
            if ($invItems && count($invItems)) {
                Log::info('Invoice Items: ' . strval(count($invItems)));
                foreach ($invItems as $invItem) {
                    $itemResp = $this->service->getItem($invItem->item_id);
                    $stockItem = @$itemResp->item;

                    if ($stockItem && $stockItem->product_type == 'service') {
                        $itemName = $stockItem->name;
                        // fetch composite items with replica name
                        $comItemResponse = $this->service->getCompositeItems(['name_contains' => $itemName]);
                        $comItems = @$comItemResponse->composite_items;
                        Log::info('Composite Items: ' . strval(count($comItems)));

                        if ($comItems && count($comItems)) {
                            $comItem = $comItems[0];
                            // fetch specific composite item 
                            $comItemResponse1 = $this->service->getCompositeItem($comItem->composite_item_id);
                            $mappedItems = @$comItemResponse1->composite_item->mapped_items;
                            Log::info('Mapped Composite Items: ' . strval(count($mappedItems)));

                            // post inventory adjustment for mapped items
                            if ($mappedItems && count($mappedItems)) {
                                $adjustmentLines = [];
                                foreach ($mappedItems as $mappedItem) {
                                    if ($mappedItem->product_type == 'goods') {
                                        $adjustmentLines[] = [
                                            "item_id" => $mappedItem->item_id,
                                            "quantity_adjusted" => -$mappedItem->quantity*$invItem->quantity
                                        ];
                                    }
                                }
                                Log::info('Adjustment Lines: ' . strval(count($adjustmentLines)));
                                $adjResponses[] = $this->service->postInventoryAdjustment([
                                  "reason" => "Inventory Revaluation",
                                  "description" => "Sales Invoice {$zohoInvoice->invoice_number}",
                                  "adjustment_type" => "quantity",
                                  "date" => $zohoInvoice->date,
                                  "location_id" => (string) (@$zohoInvoice->location_id ?: @$zohoInvoice->warehouse_id), // dynamic
                                  "line_items" => $adjustmentLines,
                                  "status" => $zohoInvoice->status == 'draft'? 'draft' : '',
                                ]);                            
                            }
                        }
                    }
                }
            }
            $invoice->update([
                'zoho_invoice_id' => $zohoInvoice->invoice_id,
                'zoho_invoice_number' => $zohoInvoice->invoice_number,
                'zoho_status' => $zohoInvoice->status,
                'zoho_exchange_rate' => $zohoInvoice->exchange_rate,
                'zoho_invoice_url' => $zohoInvoice->invoice_url,
            ]);

            // create local inventory adjustment
            foreach ($adjResponses as $adjResponse) {
                $zohoAdj = @$adjResponse->inventory_adjustment;
                if ($zohoAdj) {
                    $zohoAdjs[] = $zohoAdj;
                    $stockAdj = $invoice->stockAdj()->create([
                        'reason' => $zohoAdj->reason,
                        'description' => $zohoAdj->description,
                        'adjustment_type' => $zohoAdj->adjustment_type,
                        'date' => $zohoAdj->date,
                        'location_id' => (string) (@$zohoAdj->location_id ?: @$zohoAdj->warehouse_id),
                        'invoice_id' => $invoice->id,
                        'zoho_inventory_adjustment_id' => $zohoAdj->inventory_adjustment_id,
                    ]);
                    foreach ($zohoAdj->line_items as $item) {
                        $stockAdj->items()->create([
                            'stock_adj_id' => $stockAdj->id,
                            'item_id' => $item->item_id,
                            'quantity_adjusted' => $item->quantity_adjusted,
                            'zoho_line_item_id' => $item->line_item_id,
                            'zoho_item_name' => $item->name,
                            'zoho_adjustment_account_id' => $item->adjustment_account_id,
                            'zoho_adjustment_account_name' => $item->adjustment_account_name,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success', 
                'message' => 'Invoice updated successfully',
                'redirectTo' => route('invoices.index'),
            ]);
        } catch (Exception $e) {
            $msg = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            Log::error($msg);
            $msg = $e->getMessage();

            // capture Zoho Error
            if ($e instanceof RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = (string) $response->getBody();
                // Try to decode JSON error message
                $error = json_decode($errorBody, true);
                $msg = "Zoho Error: " . ($error['message'] ?? $errorBody);
                Log::error($msg);
            }             
            
            return response()->json([
                'status' => 'error', 
                'message' => 'Invoice update failed! ' . $msg,
            ]);
        }        
    }

    /**
     * Back Up Remote Data
     * */
    public function backupRemoteData($invoice)
    {
        try {
            $invoiceResp = $this->service->getInvoice($invoice->zoho_invoice_id);
            $adjustmentResp = $this->service->getInventoryAdjustment(@$invoice->stockAdj->zoho_inventory_adjustment_id);

            $invoiceBuk = "";
            $adjustmentBuk = "";
            if (isset($invoiceResp->invoice)) {
                $invoiceBuk = json_encode($invoiceResp->invoice);
                $invoice->update([
                    'invoice_buk' => $invoiceBuk,
                ]);
            }
            if (isset($adjustmentResp->inventory_adjustment)) {
                $adjustmentBuk = json_encode($adjustmentResp->inventory_adjustment);
                $invoice->update([
                    'stockadj_buk' => $adjustmentBuk,
                ]);
            }
            Log::info('Invoice-buk: ' . $invoiceBuk);
            Log::info('Adjustment-buk: ' . $adjustmentBuk);

            return true;
        } catch (Exception $e) {
            $msg = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            Log::error($msg);
            $msg = $e->getMessage();

            // capture Zoho Error
            if ($e instanceof RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = (string) $response->getBody();
                // Try to decode JSON error message
                $error = json_decode($errorBody, true);
                $msg = "Zoho Error: " . ($error['message'] ?? $errorBody);
                Log::error($msg);
            } 
            
            return response()->json([
                'status' => 'error', 
                'message' => 'Invoice update failed! ' . $msg,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        try {   
            $userId = auth()->user()->id;
            $invoice->update(['deleted_at' => now(), 'deleted_by' => $userId]);
            if ($invoice->stockAdj) {
                $invoice->stockAdj()->update(['deleted_at' => now(), 'deleted_by' => $userId]); 
            }

            return redirect(route('invoices.index'))->with(['success' => 'Invoice deleted successfully']);
        } catch (\Throwable $th) {
            return errorHandler('Error deleting Invoice!', $th);
        }
    }

    /**
     * Update Invoice Status
     * */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required',
            'status' => 'required',
        ]);

        try {   
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);
            $invoice->update(['zoho_status' => $request->status]);

            $this->service->markSentInvoice($invoice->zoho_invoice_id);
            $adjustmentId = @$invoice->stockAdj->zoho_inventory_adjustment_id;
            if ($adjustmentId) $this->service->markInventoryAdjustment($adjustmentId);

            DB::commit();
            return redirect(route('invoices.index'))->with(['success' => 'Invoice status updated successfully']);
        } catch (\Exception $e) {
            // capture Zoho Error
            if ($e instanceof RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = (string) $response->getBody();
                // Try to decode JSON error message
                $error = json_decode($errorBody, true);
                $msg = "Zoho Error: " . ($error['message'] ?? $errorBody);
                Log::error($msg);
                return errorHandler($msg);
            } 
            return errorHandler('Error updating invoice status', $e);
        }
    }

    /**
     * Get Invoice due-date
     */
    public function getDuedate(Request $request)
    {
        $date = databaseDate($request->date);
        $terms = (int) $request->terms;
        $duedate = $date;
        if ($terms > 0) {
            $duedate = date('Y-m-d', strtotime($date . " +{$terms} days"));
        } 

        return response()->json(compact('duedate'));
    }
    

    /** 
     * Search Contacts
     * */
    public function searchContacts(Request $request)
    {
        $params = $request->all();
        $contacts = $this->service->getContacts($params);
        
        return response()->json($contacts);
    }

    /** 
     * Search Sales Person
     * */
    public function searchSalesPersons(Request $request)
    {
        $params = $request->all();
        $salesPerson = $this->service->getSalesPerson($params);
        
        return response()->json($salesPerson);
    }

    /** 
     * Search Items
     * */
    public function searchItems(Request $request)
    {
        $params = $request->all();
        $itemsObj = $this->service->getItems($params);
        $items = @$itemsObj->items ?: [];

        // filter out composite items
        $sku = config('ZOHO_COMPOSITE_SKU');
        if ($sku) $items = array_filter($items, fn($v) => !(stripos($v->sku, $sku) !== false));
        
        return view('invoices.partial.dropdown_item', compact('items'));
    }

    /** 
     * Payment Terms
     * */
    public function paymentTerms(Request $request)
    {
        $params = $request->all();
        $terms = $this->service->paymentTerms($params);
        
        return response()->json($terms);
    }

    /** 
     * Item Locations
     * */
    public function itemLocations(Request $request)
    {
        $params = $request->all();
        $locations = [];

        try {
            $locations = $this->service->getLocations($params);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $locations = $this->service->getWarehouses($params);
        }
        
        return response()->json($locations);
    }

    /** 
     * Currencies
     * */
    public function currencies(Request $request)
    {
        $params = $request->all();
        $currencies = $this->service->getCurrencies($params);
        
        return response()->json($currencies);
    }
}
