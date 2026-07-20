<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ZohoService
{
    public function postRequest($url='',$query=[],$content=[])
    {
        $client = new \GuzzleHttp\Client();
        $promise = $client->postAsync($url, [
            'headers' => [
                'Content-Type' => "application/json",
                'Accept' => "application/json",
                'Authorization' => 'Zoho-oauthtoken ' . config('ZOHO_ACCESS_TOKEN'),
            ],
            'query' => $query,
            'json' => $content,
        ]);
        
        $responseData = (object) [];
        $promise->then(
            function ($response) use(&$responseData){
                $responseData = json_decode($response->getBody()->getContents());
            },
            function (Exception $e) {
                Log::error("Zoho Post Request Error: " . $e->getMessage());
            }
        );
        $promise->wait();

        return $responseData;
    }

    public function putRequest($url='',$query=[],$content=[])
    {
        $client = new \GuzzleHttp\Client();
        $promise = $client->putAsync($url, [
            'headers' => [
                'Content-Type' => "application/json",
                'Accept' => "application/json",
                'Authorization' => 'Zoho-oauthtoken ' . config('ZOHO_ACCESS_TOKEN'),
            ],
            'query' => $query,
            'json' => $content,
        ]);
        
        $responseData = (object) [];
        $promise->then(
            function ($response) use(&$responseData){
                $responseData = json_decode($response->getBody()->getContents());
            },
            function (Exception $e) {
                Log::error("Zoho Post Request Error: " . $e->getMessage());
            }
        );
        $promise->wait();

        return $responseData;
    }

    public function getRequest($url='',$query=[])
    {
        $client = new \GuzzleHttp\Client();
        $promise = $client->getAsync($url, [
            'headers' => [
                'Content-Type' => "application/json",
                'Accept' => "application/json",
                'Authorization' => 'Zoho-oauthtoken ' . config('ZOHO_ACCESS_TOKEN'),
            ],
            'query' => $query,
        ]);
        
        $responseData = (object) [];
        $promise->then(
            function ($response) use(&$responseData){
                $responseData = json_decode($response->getBody()->getContents());
            },
            function (Exception $e) {
                Log::error('Zoho Error, ' . $e->getMessage());
            }
        );
        $promise->wait();

        return $responseData;
    }

    public function deleteRequest($url='',$query=[])
    {
        $client = new \GuzzleHttp\Client();
        $promise = $client->deleteAsync($url, [
            'headers' => [
                'Content-Type' => "application/json",
                'Accept' => "application/json",
                'Authorization' => 'Zoho-oauthtoken ' . config('ZOHO_ACCESS_TOKEN'),
            ],
            'query' => $query,
        ]);
        
        $responseData = (object) [];
        $promise->then(
            function ($response) use(&$responseData){
                $responseData = json_decode($response->getBody()->getContents());
            },
            function (Exception $e) {
                Log::error('Zoho Error, ' . $e->getMessage());
            }
        );
        $promise->wait();

        return $responseData;
    }

    public function refreshToken()
    {
        $url = config('ZOHO_REFRESH_TOKEN_URL');
        $query = [
            'client_id' => config('ZOHO_CLIENT_ID'),
            'client_secret' => config('ZOHO_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
            'refresh_token' => config('ZOHO_REFRESH_TOKEN'),
        ];

        $client = new \GuzzleHttp\Client();
        $promise = $client->postAsync($url, [
            'headers' => [
                'Content-Type' => "application/json",
                'Accept' => "application/json",
            ],
            'query' => $query,
        ]);
        
        $responseData = (object) [];
        $promise->then(
            function ($response) use(&$responseData){
                $responseData = json_decode($response->getBody()->getContents());
            },
            function (Exception $e) {
                Log::error('Zoho Post error, ' . $e->getMessage());
            }
        );
        $promise->wait();
        
        return $responseData;
    }

    public function getContacts($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/contacts', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getSalesPerson($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/salespersons', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getLocations($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/locations', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getWarehouses($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/warehouses', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getItems($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/items', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getItem($itemId)
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL')."/items/{$itemId}", [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ]);

        return $response;
    }

    public function getCompositeItem($compositeItemId)
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL')."/compositeitems/{$compositeItemId}", [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ]);

        return $response;
    }

    public function getCompositeItems($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/compositeitems', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function paymentTerms($params=[])
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/settings/paymentterms', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ] + $params);

        return $response;
    }

    public function getInvoice($invoiceId)
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL')."/invoices/{$invoiceId}", [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ]);

        return $response;
    }

    public function postInvoice($model)
    {   
        $lineItems = $model->items->map(function($item) {
            return [
                "item_id" => $item->item_id,
                "name" => $item->name,
                "description" => $item->description,
                "item_order" => 1,
                "bcy_rate" => (float) @$item->invoice->currency_rate,
                "rate" => $item->rate,
                "quantity" => $item->quantity,
                "unit" => $item->unit,
                "discount" => 0,
                "item_total" => $item->amount,
            ];
        });
        $invoiceData = array_replace(config('zohotemplate.invoice'), [
            'customer_id' => $model->customer_id,
            'date' => $model->date,
            'due_date' => $model->due_date,
            "payment_terms" => $model->payment_terms, 
            "payment_terms_label" => $model->payment_terms_label,
            'salesperson_name' => $model->salesperson_name,
            "is_discount_before_tax" => true,
            "discount_type" => "item_level",
            "is_inclusive_tax" => false,
            "exchange_rate" => $model->currency_rate,
            "line_items" => $lineItems,
            'sub_total' => $model->subtotal,
            'tax_total' => 0,
            'total' => $model->total,
            "notes" => $model->notes,
            "terms" => "Terms & Conditions apply",
            "shipping_charge" => 0,
            "adjustment" => 0,
            "custom_fields" => [],
        ]);
        // append custom fields
        if (isset($model->cf_loyalty_points_qualifier)) {
            $invoiceData['custom_fields'][] = [
                "api_name" => "cf_loyalty_points_qualifier",
                "value" => $model->cf_loyalty_points_qualifier,
            ];
        } elseif (isset($model->cf_redeem_points)) {
            $invoiceData['custom_fields'][] = [
                "api_name" => "cf_redeem_points",
                "value" => (bool) $model->cf_redeem_points,
            ];
        }

        // sanitize invoice data
        foreach ($invoiceData as $key => $val) {
            if ($val === '' || (is_array($val) && !$val)) {
                unset($invoiceData[$key]);
            }
        }
        // dd($invoiceData);

        $response = $this->postRequest(
            config('ZOHO_BASE_URL').'/invoices', 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')],
            $invoiceData
        );

        return $response;
    }

    public function updateInvoice($model)
    {   
        $lineItems = $model->items->map(function($item) {
            return [
                "item_id" => $item->item_id,
                "name" => $item->name,
                "description" => $item->description,
                "item_order" => 1,
                "bcy_rate" => (float) @$item->invoice->currency_rate,
                "rate" => $item->rate,
                "quantity" => $item->quantity,
                "unit" => $item->unit,
                "discount" => 0,
                "item_total" => $item->amount,
            ];
        });
        $invoiceData = array_replace(config('zohotemplate.invoice'), [
            'customer_id' => $model->customer_id,
            'date' => $model->date,
            'due_date' => $model->due_date,
            "payment_terms" => $model->payment_terms, 
            "payment_terms_label" => $model->payment_terms_label,
            'salesperson_name' => $model->salesperson_name,
            "is_discount_before_tax" => true,
            "discount_type" => "item_level",
            "is_inclusive_tax" => false,
            "exchange_rate" => $model->currency_rate,
            "line_items" => $lineItems,
            'sub_total' => $model->subtotal,
            'tax_total' => 0,
            'total' => $model->total,
            "notes" => $model->notes,
            "terms" => "Terms & Conditions apply",
            "shipping_charge" => 0,
            "adjustment" => 0,
            "reason" => 'Order details adjustment',
            "custom_fields" => [],
        ]);

        // append custom fields
        if (isset($model->cf_loyalty_points_qualifier)) {
            $invoiceData['custom_fields'][] = [
                "api_name" => "cf_loyalty_points_qualifier",
                "value" => $model->cf_loyalty_points_qualifier,
            ];
        } elseif (isset($model->cf_redeem_points)) {
            $invoiceData['custom_fields'][] = [
                "api_name" => "cf_redeem_points",
                "value" => (bool) $model->cf_redeem_points,
            ];
        }

        // sanitize
        foreach ($invoiceData as $key => $val) {
            if ($val === '' || (is_array($val) && !$val)) {
                unset($invoiceData[$key]);
            }
        }
        // dd($invoiceData);

        $response = $this->putRequest(
            config('ZOHO_BASE_URL')."/invoices/{$model->zoho_invoice_id}", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')],
            $invoiceData
        );

        return $response;
    }

    public function deleteInvoice($invoiceId)
    {
        $response = $this->deleteRequest(
            config('ZOHO_BASE_URL')."/invoices/{$invoiceId}", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')]
        );

        return $response;
    }

    public function markSentInvoice($invoiceId)
    {
        $response = $this->postRequest(
            config('ZOHO_BASE_URL')."/invoices/{$invoiceId}/status/sent", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')]
        );

        return $response;
    }

    public function getInventoryAdjustment($inventoryAdjustmentId)
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL')."/inventoryadjustments/{$inventoryAdjustmentId}", [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ]);

        return $response;
    }

    public function postInventoryAdjustment($adjustment=[])
    {
        $response = $this->postRequest(
            config('ZOHO_BASE_URL')."/inventoryadjustments", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')],
            $adjustment
        );

        return $response;
    }

    public function deleteInventoryAdjustment($inventoryAdjustmentId)
    {
        $response = $this->deleteRequest(
            config('ZOHO_BASE_URL')."/inventoryadjustments/{$inventoryAdjustmentId}", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')]
        );

        return $response;
    }

    public function markInventoryAdjustment($inventoryAdjustmentId)
    {
        $response = $this->putRequest(
            config('ZOHO_BASE_URL')."/inventoryadjustments/{$inventoryAdjustmentId}", 
            ['organization_id' => config('ZOHO_ORGANIZATION_ID')],
            ['status' => 'adjusted']
        );

        return $response;
    }

    public function getCurrencies()
    {
        $response = $this->getRequest(config('ZOHO_BASE_URL').'/settings/currencies', [
            'organization_id' => config('ZOHO_ORGANIZATION_ID'),
        ]);

        return $response;
    }
}
