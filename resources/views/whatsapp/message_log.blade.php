@extends('layouts.core')
@section('title', 'WhatsApp Log')
    
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">WhatsApp Log</h5>
            <div class="card-content p-2">
                <div class="table-responsive">
                    <!-- LIMIT CONTROL -->
                    <label>
                        <select id="dbLimit" onchange="updateLimit()">
                          <option value="10">10</option>
                          <option value="25">25</option>
                          <option value="50">50</option>
                          <option value="200">200</option>
                        </select> entries per page
                      </label>  

                    <table class="table table-borderless" id="messageLogs">
                        <thead>
                            <tr>
                                <th>DATE</th>
                                <th>SENDER</th>
                                <th>RECEPIENT</th>
                                <th>CONTENT</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="100%">{!! spinner() !!}</td></tr>
                        </tbody>                        
                    </table>
                </div>

                <!-- Custom Database Pagination Sync Controllers -->
                <div class="custom-pagination">
                  <button class="btn btn-outline-primary btn-sm" onclick="navigatePage(-1)">Previous</button>
                  <span id="pageLabel">Page 1</span>
                  <button class="btn btn-outline-primary btn-sm" onclick="navigatePage(1)">Next</button>
                </div>
            </div>
        </div>
    </div>
    {{-- @include('invoices.partial.status_modal') --}}
@stop

@section('script')
<script>
    // 1. Initialize simple-datatables with client paging off
    let dataTableInstance = new simpleDatatables.DataTable("#messageLogs", {
        paging: false, // 👈 Stop client-side paging; backend handles it
        searchable: true,
        sortable: true
    });

    // 2. Global Pagination State
    let currentPage = 1;
    let currentLimit = 10;

    // 3. Main Data Orchestrator
    async function loadServerData() {
        // Calculate standard database offset
        const calculatedOffset = (currentPage - 1) * currentLimit;

        try {
            // Fetch specific slice from your DB
            const url = "{{ route('whatsapp.message_log_datatable') }}";
            const response = await fetch(`${url}?limit=${currentLimit}&offset=${calculatedOffset}`);
            const htmlRows = await response.text(); // 👈 Use .text() instead of .json()

            // 3. Properly cycle the datatable instance to prevent memory leaks
            if (dataTableInstance) {
                dataTableInstance.destroy();
            }

            // 4. Inject the raw HTML string into the empty table body container
            document.querySelector("#messageLogs tbody").innerHTML = htmlRows;

            // 5. Re-initialize the library so it can read and style the new HTML
            dataTableInstance = new simpleDatatables.DataTable("#messageLogs", {
                paging: false, // Keep disabled so it doesn't conflict with server pages
                searchable: true,
                sortable: true
            });           

            // Update UI indicator text
            document.getElementById("pageLabel").textContent = `Page ${currentPage}`;
        } catch (error) {
            console.error("Database data synchronization failed:", error);
        }
    }

    // 4. UI Interaction Handlers
    function updateLimit() {
        currentLimit = parseInt(document.getElementById("dbLimit").value);
        currentPage = 1; // Reset to start index to avoid out-of-bounds offsets
        loadServerData();
    }

    function navigatePage(direction) {
        if (currentPage + direction < 1) return; // Block negative offsets
        currentPage += direction;
        loadServerData();
    }

    // Initial Boot Lifecycle Load
    document.addEventListener("DOMContentLoaded", () => {
        loadServerData();
    });
</script>    
@stop
