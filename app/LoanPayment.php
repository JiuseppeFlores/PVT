<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;
use Carbon;

class LoanPayment extends Model
{
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = true;
    public $fillable = [
        'loan_id',
        'affiliate_id',
        'pay_date',
        'estimated_date',
        'quota_number',
        'estimated_quota',
        'capital_payment',
        'interest_payment',
        'penal_payment',
        'accumulated_interest',
        'voucher_number',
        'payment_type',
        'receipt_number',
        'description'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public static function days_interest(Loan $loan, $estimated_date = null)
    {
        $interest = [
            'current' => 0,
            'penal' => 0,
            'accumulated' => 0
        ];
        if ($loan->balance == 0) return (object)$interest;
        $estimated_date = CarbonImmutable::parse($estimated_date ?? CarbonImmutable::now()->toDateString());
        $latest_quota = $loan->payments()->first();
        if (!$latest_quota) {
            $payment_date = $loan->disbursement_date;
            if (!$payment_date) return (object)$interest;
        } else {
            $payment_date = $latest_quota->pay_date;
        }
        $payment_date = CarbonImmutable::parse($payment_date);
        if ($estimated_date->lessThan($payment_date)) return (object)$interest;
        $diff_days = $estimated_date->diffInDays($payment_date) + 1;
        if ($estimated_date->diffInMonths($payment_date) == 0) {
            $interest['current'] = $diff_days;
        } else {
            $interest['current'] = $estimated_date->day;
            if ($payment_date->day >= LoanGlobalParameter::latest()->first()->offset_interest_day && $estimated_date->diffInMonths($payment_date) == 1) {
                $interest['current'] += $payment_date->endOfMonth()->diffInDays($payment_date) + 1;
            }
        }
        $interest['accumulated'] = $diff_days - $interest['current'];
        if ($interest['accumulated'] >= 90) {
            $interest['penal'] = $interest['accumulated'] - $interest['current'];
        }
        return (object)$interest;
    }

    // Unión de pagos con el mismo número de cuota
    public function merge($payments)
    {
        $merged = new LoanPayment();
        foreach ($payments as $key => $payment) {
            if ($key == 0) {
                $merged = $payment;
            } else {
                $merged->capital_payment += $payment->capital_payment;
                $merged->interest_payment += $payment->interest_payment;
                $merged->penal_payment += $payment->penal_payment;
                $merged->accumulated += $payment->accumulated;
            }
            if (!next($payments)) {
                $merged->pay_date = $payment->pay_date;
                $merged->estimated_date = $payment->estimated_date;
            }
        }
        unset($merged->affiliate_id, $merged->payment_type, $merged->voucher_number, $merged->receipt_number, $merged->description, $merged->created_at, $merged->updated_at);
        return $merged;
    }

    public static function quota_date(Loan $loan, $first = false)
    {
        $quota = 1;
        $latest_quota = $loan->last_payment();
        $estimated_date = Carbon::now()->endOfMonth();
        if (!$latest_quota || $first) {
            $payment_date = $loan->disbursement_date ? $loan->disbursement_date : $loan->request_date;
            $payment_date = CarbonImmutable::parse($payment_date);
            if ($estimated_date->lessThan($payment_date) || $first) $estimated_date = $payment_date->endOfMonth();
            if ($payment_date->day >= LoanGlobalParameter::latest()->first()->offset_interest_day && $estimated_date->diffInMonths($payment_date) == 0) {
                $estimated_date = $payment_date->startOfMonth()->addMonth()->endOfMonth();
            }
        } else {
            $estimated_date = Carbon::parse($latest_quota->pay_date)->startOfMonth()->addMonth()->endOfMonth();
            $quota = $latest_quota->quota_number + 1;
        }
        return (object)[
            'date' => $estimated_date->toDateString(),
            'quota' => $quota
        ];
    }
}
