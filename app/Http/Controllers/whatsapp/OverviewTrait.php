<?php

namespace App\Http\Controllers\whatsapp;

use App\Models\whatsapp\CustomerRating;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OverviewTrait
{
	public function feedbackKPI()
	{
		// 1. Define your date boundaries
        $now = Carbon::now();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // Base query matching your setup
        $baseQuery = CustomerRating::whereNotNull('sentiment');

        // 2. Get the count for the CURRENT 7 days (e.g., today back to 7 days ago)
        $currentCount = (clone $baseQuery)
            ->where('rating_received_at', '>=', $sevenDaysAgo)
            ->count();

        // 3. Get the count for the PREVIOUS 7 days (e.g., 7 days ago back to 14 days ago)
        $previousCount = (clone $baseQuery)
            ->where('rating_received_at', '>=', $fourteenDaysAgo)
            ->where('rating_received_at', '<', $sevenDaysAgo)
            ->count();

        // 4. Calculate percentage increase using the formula
        $percentIncrease = 0;
        if ($previousCount > 0) {
            $percentIncrease = (($currentCount - $previousCount) / $previousCount) * 100;
        } elseif ($currentCount > 0) {
            // If previous was 0 but current has data, it is a 100% growth jump
            $percentIncrease = 100; 
        }

        $feedbackChange = number_format($percentIncrease, 1);
        $feedbackReceived = $currentCount;
        return compact('feedbackChange', 'feedbackReceived');
	}

	public function averageSatisfactionKPI()
	{
		// 1. Define your date boundaries
		$sevenDaysAgo = Carbon::now()->subDays(7);
		$fourteenDaysAgo = Carbon::now()->subDays(14);

		// Base query focusing on rated feedback
		$baseQuery = CustomerRating::whereNotNull('sentiment');

		// 2. Get the AVERAGE score for the CURRENT 7 days
		$currentAvg = (clone $baseQuery)
		    ->where('rating_received_at', '>=', $sevenDaysAgo)
		    ->avg('rating_score') ?? 0; // Default to 0 if no reviews exist

		// 3. Get the AVERAGE score for the PREVIOUS 7 days
		$previousAvg = (clone $baseQuery)
		    ->where('rating_received_at', '>=', $fourteenDaysAgo)
		    ->where('rating_received_at', '<', $sevenDaysAgo)
		    ->avg('rating_score') ?? 0;

		// 4. Calculate the percentage change
		$percentIncrease = 0;

		if ($previousAvg > 0) {
		    $percentIncrease = (($currentAvg - $previousAvg) / $previousAvg) * 100;
		} elseif ($currentAvg > 0) {
		    $percentIncrease = 100; // Jump from 0 average to a positive average
		}

		// Format the output (e.g., +4.5% or -2.3%)
		$sfxnChange = number_format($percentIncrease, 1);
        $currentSfxn = $currentAvg;
        return compact('sfxnChange', 'currentSfxn');
	}

	public function netPromoterScoreKPI()
	{
		// 1. Set up 7-day and 14-day date boundaries
		$sevenDaysAgo = Carbon::now()->subDays(7);
		$fourteenDaysAgo = Carbon::now()->subDays(14);

		// 2. Base query matching rows that have an actual score
		$baseQuery = CustomerRating::whereNotNull('sentiment');

		// Extracted logic to compute NPS on a 0-4 scale
		$getNpsScore = function($query) {
		    // Perform conditional counts directly inside the SQL layer
		    $metrics = (clone $query)
		        ->select(DB::raw('
		            COUNT(*) as total,
		            SUM(CASE WHEN rating_score = 4 THEN 1 ELSE 0 END) as promoters,
		            SUM(CASE WHEN rating_score <= 2 THEN 1 ELSE 0 END) as detractors
		        '))
		        ->first();

		    if (!$metrics || $metrics->total === 0) {
		        return null; // Handle periods containing no survey data
		    }

		    $pctPromoters = ($metrics->promoters / $metrics->total) * 100;
		    $pctDetractors = ($metrics->detractors / $metrics->total) * 100;

		    return $pctPromoters - $pctDetractors; // Generates score between -100 and +100
		};

		// 3. Compute NPS metrics for both tracking brackets
		$currentNps  = $getNpsScore((clone $baseQuery)->where('rating_received_at', '>=', $sevenDaysAgo));
		$previousNps = $getNpsScore((clone $baseQuery)->where('rating_received_at', '>=', $fourteenDaysAgo)->where('rating_received_at', '<', $sevenDaysAgo));

		// 4. Calculate Percentage Increase
		$percentIncrease = 0;

		if ($previousNps !== null && $currentNps !== null) {
		    if ($previousNps == 0.0) {
		        // Prevent division-by-zero if the older NPS was exactly neutral
		        $percentIncrease = $currentNps > 0 ? 100 : ($currentNps < 0 ? -100 : 0);
		    } else {
		        // Standard formula using absolute values to correctly compute negative-to-positive score trends
		        $percentIncrease = (($currentNps - $previousNps) / abs($previousNps)) * 100;
		    }
		}

		$npsChange = number_format($percentIncrease, 1);
        return compact('npsChange', 'currentNps');
	}

	public function responseRate()
	{
		// 1. Define date boundaries
		$sevenDaysAgo = Carbon::now()->subDays(7);
		$fourteenDaysAgo = Carbon::now()->subDays(14);

        // Base query matching your setup
        $baseQuery = CustomerRating::query();

		// 2. Helper function to calculate response rate for a given time range query
		$getResponseRate = function($query) {
		    // Get total sent and total responded in a SINGLE database pass
		    $metrics = (clone $query)
		        ->select(DB::raw('
		            COUNT(*) as total_sent,
		            SUM(CASE WHEN sentiment IS NOT NULL THEN 1 ELSE 0 END) as total_responses
		        '))
		        ->first();

		    // If no requests were sent, rate is 0% to avoid division by zero
		    if (!$metrics || $metrics->total_sent === 0) {
		        return 0;
		    }

		    // Returns a raw decimal rate (e.g., 0.45 for 45%)
		    return $metrics->total_responses / $metrics->total_sent;
		};

		// 3. Get the rates for both tracking windows
		$currentRate  = $getResponseRate((clone $baseQuery)->where('rating_received_at', '>=', $sevenDaysAgo));
		$previousRate = $getResponseRate((clone $baseQuery)->where('rating_received_at', '>=', $fourteenDaysAgo)->where('rating_received_at', '<', $sevenDaysAgo));

		// 4. Calculate the percentage increase from the previous rate to current rate
		$percentIncrease = 0;

		if ($previousRate > 0) {
		    $percentIncrease = (($currentRate - $previousRate) / $previousRate) * 100;
		} elseif ($currentRate > 0) {
		    $percentIncrease = 100; // Jump from 0% response rate to a positive rate
		}

		$rateChange = number_format($percentIncrease, 1);
        return compact('rateChange', 'currentRate');
	}

	public function openIssues()
	{
		// 1. Define your 7-day and 14-day date boundaries
		$sevenDaysAgo = Carbon::now()->subDays(7);
		$fourteenDaysAgo = Carbon::now()->subDays(14);

		$baseQuery = CustomerRating::whereNotNull('sentiment');

		// 2. Build the base query for OPEN negative issues
		$baseQuery = (clone $baseQuery)
		    ->where('sentiment', 'negative');

		// 3. Get counts for both tracking frames
		$currentCount = (clone $baseQuery)
		    ->where('rating_received_at', '>=', $sevenDaysAgo)
		    ->count();

		$previousCount = (clone $baseQuery)
		    ->where('rating_received_at', '>=', $fourteenDaysAgo)
		    ->where('rating_received_at', '<', $sevenDaysAgo)
		    ->count();

		// 4. Calculate Percentage DECREASE
		$percentDecrease = 0;

		if ($previousCount > 0) {
		    // Inverted formula: ((Old - New) / Old) * 100
		    // This makes a drop in issues output as a positive percentage decrease
		    $percentDecrease = (($previousCount - $currentCount) / $previousCount) * 100;
		} elseif ($currentCount === 0 && $previousCount === 0) {
		    $percentDecrease = 0; // Perfect score: stayed at zero issues
		} else {
		    // If previous was 0 and current has issues, it's technically a negative decrease (an increase)
		    $percentDecrease = (($previousCount - $currentCount) / 1) * 100; 
		}

		// 5. Format the output string 
		// If positive, it means issues went DOWN. If negative, issues went UP.
		$issuesChange = number_format($percentDecrease, 1);
		$currentIssues = $currentCount;
        return compact('issuesChange', 'currentIssues');
	}

	public function resolvedCases()
	{
		// 1. Define your 7-day and 14-day date boundaries
		$sevenDaysAgo = Carbon::now()->subDays(7);
		$fourteenDaysAgo = Carbon::now()->subDays(14);

		$baseQuery = CustomerRating::whereNotNull('sentiment');

		// 2. Base query filtered to only items that have been resolved
		$baseQuery = (clone $baseQuery)->whereNotNull('resolved_at');

		// 3. Count resolutions that occurred in the CURRENT 7 days
		$currentResolved = (clone $baseQuery)
		    ->where('resolved_at', '>=', $sevenDaysAgo)
		    ->count();

		// 4. Count resolutions that occurred in the PREVIOUS 7 days
		$previousResolved = (clone $baseQuery)
		    ->where('resolved_at', '>=', $fourteenDaysAgo)
		    ->where('resolved_at', '<', $sevenDaysAgo)
		    ->count();

		// 5. Calculate percentage increase
		$percentIncrease = 0;

		if ($previousResolved > 0) {
		    $percentIncrease = (($currentResolved - $previousResolved) / $previousResolved) * 100;
		} elseif ($currentResolved > 0) {
		    $percentIncrease = 100; // Jump from 0 resolutions to a positive number
		}

		// 6. Format output (e.g., "+24.5%" or "-10.0%")
		$resolutionChange = number_format($percentIncrease, 1);
        return compact('resolutionChange', 'currentResolved');
	}

	public function ratingFeedback()
	{
		$ratingFeedback = CustomerRating::whereNotNull('comment_received_at')
			->latest()
			->limit(5)
			->get(['id', 'customer_name', 'sentiment', 'rating_comment', 'comment_received_at']);
			
		return compact('ratingFeedback');
	}
}