<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{$file_title}}</title>
    <link rel="stylesheet" href="{{ public_path("/css/report-print.min.css") }}" media="all"/>
</head>
<body>
    @include('partials.header', $header)
<div class="block">
        <div class="font-semibold leading-tight text-center m-b-10 text-lg">{{ $title }}</div>
        <div class="font-semibold leading-tight text-center m-b-10 text-lg">De {{ Carbon::parse($initial_date)->format('d/m/Y') }} al {{ Carbon::parse($final_date)->format('d/m/Y') }}</div>
</div>
<div>
@php ($c = 1)
@php ($sw = 1)
@php ($total_capital = 0)
@php ($total_interest = 0)
@php ($total_penal = 0)
@php ($total_interest_remaining = 0)
@php ($total_penal_remaining = 0)
@php ($total_cash = 0)
@php ($total_bank_deposit = 0)
@if ($payments->count() > 0)
@php ($modality = $payments->first()->procedure_type_loan)
@php ($modality_interest = $payments->first()->annual_interest_loan)
@endif
    <table class='table  table-condensed  table-striped text-center'>
        <tr class="bg-grey-darker text-s text-white">
            <td>N°</td>
            <td>Fecha</td>
            <td>N° de recibo</td>
            <td>Nombres y Apellidos</td>
            <td>C. I.</td>
            <td>Sector</td>
            <td>Concepto</td>
            <td>Nro de Prestamo</td>
            <td>Fecha Desembolso</td>
            <td>Monto Prestamo</td>
            <td>Capital</td>
            <td>interes</td>
            <td>Penal</td>
            <td>Interes Pendiente</td>
            <td>Penal Pendiente</td>
            <td>Efectivo</td>
            <td>Deposito Bancario</td>
            <td>Fecha deposito</td>
            <td>Nro. Bol.  Dep.</td>
        </tr>
        @foreach( $payments as $payment )
        @if($sw == 1)
            <tr><td colspan="5">{{$modality}}</td><td colspan="4">{{$modality_interest}} % Anual</td></tr>
            @php ($sw = 0)
        @endif
            @if($modality == $payment->procedure_type_loan)
                <tr>
                    <td>{{ $c }}</td>
                    <td>{{ Carbon::parse($payment->date_loan_payment)->format('d-m-Y')}}</td>
                    <td>{{ $payment->code_loan_payment }}</td>
                    <td>{{ $payment->full_name_borrower }}</td>
                    <td>{{ $payment->identity_card_borrower }}</td>
                    <td>{{ $payment->state_type_affiliate }}</td>
                    <td>{{ $payment->voucher_loan_payment }}</td>
                    <td>{{ $payment->code_loan }}</td>
                    <td>{{ Carbon::parse($payment->disbursement_date_loan)->format('d-m-Y') }}</td>
                    <td>{{ Util::money_format($payment->amount_approved_loan) }}</td>
                    <td>{{ Util::money_format($payment->capital_payment) }}</td>
                    <td>{{ Util::money_format($payment->interest_payment) }}</td>
                    <td>{{ Util::money_format($payment->penal_payment) }}</td>
                    <td>{{ Util::money_format($payment->interest_remaining) }}</td>
                    <td>{{ Util::money_format($payment->penal_remaining) }}</td>
                    @if ($payment->voucher_type_loan_payment == "Efectivo")
                    <td>{{ $payment->voucher_type_loan_payment }}</td>
                    @else
                    <td>0</td>
                    @endif
                    @if ($payment->voucher_type_loan_payment == "Deposito Bancario")
                        <td>{{ $payment->voucher_type_loan_payment }}</td>
                    @else
                        <td>0</td>
                    @endif
                    @if($payment->estimated_date_loan_payment != null)
                        <td>{{ Carbon::parse($payment->estimated_date_loan_payment)->format('d-m-Y') }}</td>
                    @else
                        <td>//</td>
                    @endif
                    <td>{{ $payment->code_voucher }}</td>
                        @php ($c++)
                        @php ($total_capital = $total_capital + $payment->capital_payment)
                        @php ($total_interest = $total_interest + $payment->interest_payment)
                        @php ($total_penal = $total_penal + $payment->penal_payment)
                        @php ($total_interest_remaining = $total_interest_remaining + $payment->interest_remaining)
                        @php ($total_penal_remaining = $total_penal_remaining + $payment->penal_remaining)
                        @php ($total_cash = $total_cash)
                        @php ($total_bank_deposit = $total_bank_deposit)
                </tr>
            @else
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{Util::money_format($total_capital)}}</td>
                    <td>{{Util::money_format($total_interest)}}</td>
                    <td>{{Util::money_format($total_penal)}}</td>
                    <td>{{Util::money_format($total_interest_remaining)}}</td>
                    <td>{{Util::money_format($total_penal_remaining)}}</td>
                    <td>{{Util::money_format($total_cash)}}</td>
                    <td>{{Util::money_format($total_bank_deposit)}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @php ($total_capital = 0)
                @php ($total_interest = 0)
                @php ($total_penal = 0)
                @php ($total_interest_remaining = 0)
                @php ($total_penal_remaining = 0)
                @php ($total_cash = 0)
                @php ($total_bank_deposit = 0)
                @php ($modality = $payment->procedure_type_loan)
                @php ($c=1)
                <tr><td colspan="5">{{$modality}}</td><td colspan="4">{{$modality_interest}} % Anual</td></tr>
                <tr>
                    <td>{{ $c }}</td>
                    <td>{{ Carbon::parse($payment->date_loan_payment)->format('d-m-Y')}}</td>
                    <td>{{ $payment->code_loan_payment }}</td>
                    <td>{{ $payment->full_name_borrower }}</td>
                    <td>{{ $payment->identity_card_borrower }}</td>
                    <td>{{ $payment->state_type_affiliate }}</td>
                    <td>{{ $payment->voucher_loan_payment }}</td>
                    <td>{{ $payment->code_loan }}</td>
                    <td>{{ Carbon::parse($payment->disbursement_date_loan)->format('d-m-Y') }}</td>
                    <td>{{ Util::money_format($payment->amount_approved_loan) }}</td>
                    <td>{{ Util::money_format($payment->capital_payment) }}</td>
                    <td>{{ Util::money_format($payment->interest_payment) }}</td>
                    <td>{{ Util::money_format($payment->penal_payment) }}</td>
                    <td>{{ Util::money_format($payment->interest_remaining) }}</td>
                    <td>{{ Util::money_format($payment->penal_remaining) }}</td>
                    @if ($payment->voucher_type_loan_payment == "Efectivo")
                    <td>{{ $payment->voucher_type_loan_payment }}</td>
                    @else
                    <td>0</td>
                    @endif
                    @if ($payment->voucher_type_loan_payment == "Deposito Bancario")
                        <td>{{ $payment->voucher_type_loan_payment }}</td>
                    @else
                        <td>0</td>
                    @endif
                    @if($payment->estimated_date_loan_payment != null)
                        <td>{{ Carbon::parse($payment->estimated_date_loan_payment)->format('d-m-Y') }}</td>
                    @else
                        <td>//</td>
                    @endif
                    <td>{{ $payment->code_voucher }}</td>
                        @php ($c++)
                        @php ($total_capital = $total_capital + $payment->capital_payment)
                        @php ($total_interest = $total_interest + $payment->interest_payment)
                        @php ($total_penal = $total_penal + $payment->penal_payment)
                        @php ($total_interest_remaining = $total_interest_remaining + $payment->interest_remaining)
                        @php ($total_penal_remaining = $total_penal_remaining + $payment->penal_remaining)
                        @php ($total_cash = $total_cash)
                        @php ($total_bank_deposit = $total_bank_deposit)
                </tr>
            @endif

        @endforeach
        @if($payments->count() > 0)
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{Util::money_format($total_capital)}}</td>
                    <td>{{Util::money_format($total_interest)}}</td>
                    <td>{{Util::money_format($total_penal)}}</td>
                    <td>{{Util::money_format($total_interest_remaining)}}</td>
                    <td>{{Util::money_format($total_penal_remaining)}}</td>
                    <td>{{Util::money_format($total_cash)}}</td>
                    <td>{{Util::money_format($total_bank_deposit)}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
        @endif
    </table>
</div>
</body>
</html>
