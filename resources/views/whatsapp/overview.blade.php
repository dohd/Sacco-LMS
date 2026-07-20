@extends('layouts.core')
@section('title', 'Whatsapp Overview')

@section('content')
<style>
  body { background:#f8fafc; }
  .card-box { border:1px solid #e5e7eb; border-radius:14px; background:#fff; box-shadow:0 4px 16px rgba(0,0,0,.03); }
  .icon-circle { width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .green { background:#17b65c; }
  .blue { background:#2ea8e6; }
  .purple { background:#635bff; }
  .yellow { background:#f8b400; }
  .metric-title { font-size:13px; color:#374151; }
  .metric-value { font-size:28px; font-weight:700; }
  .small-up { color:#16a34a; font-size:12px; }
  .small-down { color:#dc2626; font-size:12px; }
  .status-pill { padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; }
</style>

<main>
  <div class="pagetitle">
    <h1>Whatsapp Overview</h1>
  </div>
  <!-- End Page Title -->

  <section class="section dashboard">
    <div class="container-fluid px-2 py-2">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
              {{-- <h3 class="fw-bold mb-0">Dashboard</h3> --}}
              <small class="text-muted">Overview of customer feedback</small>
          </div>

          <div class="d-flex gap-2">
              <button class="btn btn-light border">{{ $lastWeekDate }} - {{ date('M d, Y') }} <i class="bi bi-calendar ms-2"></i></button>
              <button class="btn btn-light border">Last 7 Days <i class="bi bi-chevron-down ms-2"></i></button>
              <button class="btn btn-success border"><i class="bi bi-arrow-clockwise"></i></button>
              {{-- <button class="btn btn-success">Export</button> --}}
          </div>
      </div>

      <div class="row g-3 mb-4" id="kpiCards"></div>

      {{-- <div class="row g-3 mb-4">
          <div class="col-lg-5">
              <div class="card-box p-4">
                  <div class="d-flex justify-content-between mb-3">
                      <h6 class="fw-bold">Satisfaction Trend</h6>
                      <button class="btn btn-sm btn-light border">Daily</button>
                  </div>
                  <div id="satisfactionChart"></div>
              </div>
          </div>

          <div class="col-lg-3">
              <div class="card-box p-4 h-100">
                  <h6 class="fw-bold mb-3">Sentiment Overview</h6>
                  <div id="sentimentChart"></div>
              </div>
          </div>

          <div class="col-lg-4">
              <div class="card-box p-4">
                  <div class="d-flex justify-content-between mb-3">
                      <h6 class="fw-bold">Feedback Volume</h6>
                      <button class="btn btn-sm btn-light border">Daily</button>
                  </div>
                  <div id="feedbackVolumeChart"></div>
              </div>
          </div>
      </div> --}}

      <div class="row g-3 mb-4">
          <div class="col-lg-7">
              <div class="card-box p-4">
                  <div class="d-flex justify-content-between mb-3">
                      <h6 class="fw-bold">Recent Feedback</h6>
                      <a href="{{ route('whatsapp.customer_rating') }}" class="btn btn-sm btn-light border">View All</a>
                  </div>

                  <div class="table-responsive">
                      <table class="table align-middle">
                          <thead class="small text-muted">
                              <tr>
                                  <th>Customer</th>
                                  <th>Message</th>
                                  <th>Sentiment</th>
                                  <th>Time</th>
                              </tr>
                          </thead>
                          <tbody id="feedbackRows"></tbody>
                      </table>
                  </div>
              </div>
          </div>

          <div class="col-lg-5">
              <div class="card-box p-4">
                  <h6 class="fw-bold mb-4">Response Funnel</h6>
                  <div class="d-flex justify-content-around text-center">
                      <div><i class="bi bi-send fs-3 text-success"></i><br><b>3,210</b><br><small>Sent</small></div>
                      <div><i class="bi bi-check-circle fs-3 text-success"></i><br><b>2,982</b><br><small>Delivered</small></div>
                      <div><i class="bi bi-eye fs-3 text-success"></i><br><b>2,356</b><br><small>Read</small></div>
                      <div><i class="bi bi-chat fs-3 text-success"></i><br><b>1,248</b><br><small>Responded</small></div>
                  </div>
              </div>              
          </div>
      </div>
    </div>
  </section>
</main>
@stop


@section('script')
<script>
$(function () {
    let data = @json($__data);
    console.log(data);

    const kpis = [
        { title: "Feedback Received", value: "{{ $feedbackKPI['feedbackReceived'] }}", icon: "bi-chat-fill", color: "green", change: "↑ {{ +$feedbackKPI['feedbackChange'] }}% vs previous 7 days", up: true },
        { title: "Average Satisfaction", value: "{{ +$averageSatisfactionKPI['currentSfxn'] }}/4", icon: "bi-star-fill", color: "green", change: "↑ {{ +$averageSatisfactionKPI['sfxnChange'] }} vs previous 7 days", up: true },
        { title: "Net Promoter Score", value: "{{ $netPromoterScoreKPI['currentNps'] }}", icon: "bi-graph-up-arrow", color: "purple", change: "↑ {{ +$netPromoterScoreKPI['npsChange'] }} vs previous 7 days", up: true },
        { title: "Response Rate", value: "{{ $responseRateKPI['currentRate'] }}%", icon: "bi-send-fill", color: "blue", change: "↑ {{ +$responseRateKPI['rateChange'] }}% vs previous 7 days", up: true },
        { title: "Open Issues", value: "{{ $openIssuesKPI['currentIssues'] }}", icon: "bi-exclamation-triangle", color: "yellow", change: "↓ {{ abs($openIssuesKPI['issuesChange']) }} vs previous 7 days", up: false },
        { title: "Resolved Cases", value: "{{ $resolvedCasesKPI['currentResolved'] }}", icon: "bi-check-circle", color: "green", change: "↑ {{ +$resolvedCasesKPI['resolutionChange'] }} vs previous 7 days", up: true }
    ];

    $.each(kpis, function(index, item) {
        $('#kpiCards').append(`
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card-box p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-circle ${item.color}">
                            <i class="bi ${item.icon}"></i>
                        </div>
                        <div>
                            <div class="metric-title">${item.title}</div>
                            <div class="metric-value">${item.value}</div>
                            <div class="${item.up ? 'small-up' : 'small-down'}">${item.change}</div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });

    const feedback_ = [
        ["Alex M.", "Very happy with the service and fast response.", "Positive", "10:24 AM"],
        ["Naomi W.", "My order was delayed and only received after 5 days.", "Negative", "09:15 AM",],
        ["Kevin E.", "Good product quality but delivery could be faster.", "Neutral", "Yesterday",],
        ["Sharon N.", "Excellent customer support! Keep it up.", "Positive", "Yesterday",],
        ["Brian M.", "Payment failed multiple times but was charged.", "Negative", "May 23",]
    ];

    const feedback = @json($ratingFeedbackKPI['ratingFeedback']);

    $.each(feedback, function(index, row) {
        let sentimentClass = row['sentiment'] === 'positive' ? 'bg-success-subtle text-success' :
                             row['sentiment'] === 'negative' ? 'bg-danger-subtle text-danger' :
                             'bg-warning-subtle text-warning';
        const time = row['comment_received_at'].split(' ')[1];                   

        $('#feedbackRows').append(`
            <tr>
                <td><b>${row['customer_name']}</b></td>
                <td>${row['rating_comment']}</td>
                <td><span class="status-pill ${sentimentClass}">${row['sentiment']}</span></td>
                <td>${time}</td>
            </tr>
        `);
    });

    const complaints = [
        ["Delivery Delay", "32%"],
        ["Payment Issues", "21%"],
        ["Product Quality", "16%"],
        ["Customer Service", "14%"],
        ["Pricing", "9%"]
    ];

    const compliments = [
        ["Friendly Staff", "29%"],
        ["Fast Delivery", "25%"],
        ["Good Quality", "20%"],
        ["Easy to Use", "15%"],
        ["Great Support", "11%"]
    ];

    $.each(complaints, function(i, item) {
        $('#complaints').append(`<div class="d-flex justify-content-between small mb-3"><span>${item[0]}</span><b>${item[1]}</b></div>`);
    });

    $.each(compliments, function(i, item) {
        $('#compliments').append(`<div class="d-flex justify-content-between small mb-3"><span>${item[0]}</span><b>${item[1]}</b></div>`);
    });

    new ApexCharts(document.querySelector("#satisfactionChart"), {
        chart: { type: 'area', height: 260, toolbar: { show: false } },
        series: [{ name: 'Satisfaction', data: [4.0, 4.1, 4.2, 4.6, 4.55, 4.25, 4.4] }],
        xaxis: { categories: ['May 19', 'May 20', 'May 21', 'May 22', 'May 23', 'May 24', 'May 25'] },
        yaxis: { min: 1, max: 5 },
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 5 },
        dataLabels: { enabled: false },
        colors: ['#16a34a'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.05 } }
    }).render();

    new ApexCharts(document.querySelector("#sentimentChart"), {
        chart: { type: 'donut', height: 260 },
        series: [898, 225, 125],
        labels: ['Positive', 'Neutral', 'Negative'],
        colors: ['#16a34a', '#fbbf24', '#ef4444'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Feedback',
                            formatter: () => '1,248'
                        }
                    }
                }
            }
        }
    }).render();

    new ApexCharts(document.querySelector("#feedbackVolumeChart"), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [{ name: 'Feedback', data: [210, 220, 252, 245, 225, 198, 235] }],
        xaxis: { categories: ['May 19', 'May 20', 'May 21', 'May 22', 'May 23', 'May 24', 'May 25'] },
        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
        dataLabels: { enabled: false },
        colors: ['#16a34a']
    }).render();

    new ApexCharts(document.querySelector("#slaChart"), {
        chart: { type: 'donut', height: 220 },
        series: [21, 6, 5],
        labels: ['Within SLA', 'Due Soon', 'Overdue'],
        colors: ['#16a34a', '#fbbf24', '#ef4444'],
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Open Issues',
                            formatter: () => '32'
                        }
                    }
                }
            }
        }
    }).render();
});
</script>
@stop