<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Affiliate;
use Util;
use App\ProcedureModality;
use App\Loan;
use App\Http\Requests\CalculatorForm;


/** @group Préstamos
* Simulador de la calculadora
*/
class CalculatorController extends Controller
{
    /**
    * Calculadora
    * @bodyParam procedure_modality_id integer required ID de modalidad. Example: 32
    * @bodyParam amount_requested integer required monto solicitado. Example: 2000
    * @bodyParam months_term integer required plazo. Example: 2
    * @bodyParam affiliate_id integer required ID del afiliado. Example: 1
    * @bodyParam parent_loan_id integer ID de Préstamo Padre.. Example: 1
    * @bodyParam debtor boolean Afiliado con deudas pendientes con otras entidades . Example: true
    * @bodyParam guarantor boolean Afiliado evaluado como garante. Example: true
    * @bodyParam contributions[0].payable_liquid integer required Líquido pagable. Example: 2000
    * @bodyParam contributions[0].seniority_bonus integer required Bono Cargo . Example: 0.00
    * @bodyParam contributions[0].border_bonus integer required Bono Frontera . Example: 0.00
    * @bodyParam contributions[0].public_security_bonus integer required Bono Seguridad Ciudadana . Example: 0.00
    * @bodyParam contributions[0].east_bonus integer required Bono Oriente. Example: 0.00
    * @bodyParam contributions[1].payable_liquid integer Líquido pagable. Example: 2270
    * @bodyParam contributions[1].seniority_bonus integer Bono Cargo . Example: 0.00
    * @bodyParam contributions[1].border_bonus integer Bono Frontera . Example: 0.00
    * @bodyParam contributions[1].public_security_bonus integer Bono Seguridad Ciudadana . Example: 0.00
    * @bodyParam contributions[1].east_bonus integer Bono Oriente. Example: 0.00
    * @bodyParam contributions[2].payable_liquid integer Líquido pagable. Example: 1563
    * @bodyParam contributions[2].seniority_bonus integer Bono Cargo . Example: 0.00
    * @bodyParam contributions[2].border_bonus integer Bono Frontera . Example: 0.00
    * @bodyParam contributions[2].public_security_bonus integer Bono Seguridad Ciudadana . Example: 0.00
    * @bodyParam contributions[2].east_bonus integer Bono Oriente. Example: 0.00
    * @authenticated
    * @responseFile responses/calculator/store.200.json
    */
    public function store(CalculatorForm $request)
    {
        if(!$request->debtor){
            $procedure_modality = ProcedureModality::findOrFail($request->procedure_modality_id);
            $amount_requested = $request->amount_requested;$months_term = $request->months_term;
            $affiliate = Affiliate::findOrFail($request->affiliate_id);
            if ($request->has('parent_loan_id')) {
                $parent_loan = Loan::with(['lenders'=> function($q) use ($affiliate) {
                    $q->where('affiliate_id', $affiliate->id);
                }])->whereId($request->parent_loan_id)->first();
                if (!$parent_loan) abort(404);
                $parent_quota = $parent_loan->next_payment()->estimated_quota * $parent_loan->lenders[0]->pivot->payment_percentage/100;
            } else {
                $parent_quota = 0;
            }
            $contributions = collect($request->contributions);
            $payable_liquid_average = $contributions->avg('payable_liquid');
            $contribution_first = $contributions->first();
            $total_bonuses = $contribution_first['seniority_bonus']+$contribution_first['border_bonus']+$contribution_first['public_security_bonus']+$contribution_first['east_bonus'];
            $liquid_qualification=$this->liquid_qualification($payable_liquid_average, $total_bonuses, $affiliate, $parent_quota, $request->guarantor);
            $quota_calculation = $this->quota_calculator($procedure_modality, $request->months_term, $amount_requested);
            $maximum_suggested_amount = $this->maximum_amount($procedure_modality,$request->months_term,$liquid_qualification);
            if($amount_requested>$maximum_suggested_amount){
                $quota_calculation = $this->quota_calculator($procedure_modality, $request->months_term, $maximum_suggested_amount);
                $amount_requested = $maximum_suggested_amount;
            }
            if($payable_liquid_average!=0){
                $index_calculated =$quota_calculation/($liquid_qualification)*100 ;
            }else{
                $index_calculated = 0;
            }
            return response()->json([
                'promedio_liquido_pagable'=>round($payable_liquid_average),
                'total_bonos'=>$total_bonuses,
                'liquido_para_calificacion'=>round($liquid_qualification),
                'calculo_de_cuota'=>Util::money_format($quota_calculation),
                'indice_endeudamiento'=>intval($index_calculated),
                'monto solicitado'=>$amount_requested,
                'monto_maximo_sugerido'=>$maximum_suggested_amount,
                'valido' => ($index_calculated) <= ($procedure_modality->loan_modality_parameter->decimal_index)*100
            ]);
        }
        abort (403, 'El afiliado no puede ser evaluado');
    }
    // funcion para sacar la cuota estimada con la calculadora
    private function quota_calculator($procedure_modality, $months_term, $amount_requested){
        $interest_rate = $procedure_modality->current_interest->monthly_current_interest;
        return ((($interest_rate)/(1-(1/pow((1+$interest_rate),$months_term))))*$amount_requested);
    }
    // funcion para sacar el liquido para calificacion
    private function liquid_qualification($payable_liquid_average, $total_bonuses, $affiliate, $parent_quota, $guarantor){
        $active_guarantees = $affiliate->active_guarantees(); $sum_quota_guarantor = 0;
        foreach($active_guarantees as $res){
            $sum_quota_guarantor+= ($res->estimated_quota*$res->pivot->payment_percentage)/100; // en caso de tener garantias
        }
        if($guarantor){
            $active_loans = $affiliate->active_loans(); $sum_quota_lender = 0;
            foreach($active_loans as $res){
                $sum_quota_lender+= ($res->estimated_quota*$res->pivot->payment_percentage)/100; // en caso de ser garante y tener prestamos propios
            }
            $liquid_qualification = $payable_liquid_average - $total_bonuses - $sum_quota_guarantor - $sum_quota_lender;
        }else{
            $liquid_qualification  = $payable_liquid_average - $total_bonuses - $sum_quota_guarantor + $parent_quota;
        }
        return $liquid_qualification;
    }

    // monto maximo
    private function maximum_amount($procedure_modality,$months_term,$liquid_qualification){
        $interest_rate = $procedure_modality->current_interest->monthly_current_interest;
        $loan_interval = $procedure_modality->procedure_type->interval;
        $debt_index = $procedure_modality->loan_modality_parameter->decimal_index;
        $maximum_qualified_amount = intval((1-(1/pow((1+$interest_rate),$months_term)))*($debt_index*$liquid_qualification)/$interest_rate);
        if ($maximum_qualified_amount > ($loan_interval->maximum_amount)){
            $maximum_qualified_amount = $loan_interval->maximum_amount;
        } else {
            $maximum_qualified_amount = $maximum_qualified_amount;
        }
        return intval(round(floor($maximum_qualified_amount))/100)*100;
    }
}