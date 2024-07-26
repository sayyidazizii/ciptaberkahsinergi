<?php
namespace App\Imports;

use App\Models\MigrationBalanceSheet;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BalanceSheetImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new MigrationBalanceSheet([
            'balance_sheet_report_id' => $row['balance_sheet_report_id'],
            'report_no' => $row['report_no'],
            'account_id1' => $row['account_id1'],
            'account_code1' => $row['account_code1'],
            'account_name1' => $row['account_name1'],
            'account_amount1' => $row['account_amount1'],
            'account_id2' => $row['account_id2'],
            'account_code2' => $row['account_code2'],
            'account_name2' => $row['account_name2'],
            'account_amount2' => $row['account_amount2'],
            'report_formula1' => $row['report_formula1'],
            'report_operator1' => $row['report_operator1'],
            'report_type1' => $row['report_type1'],
            'report_tab1' => $row['report_tab1'],
            'report_bold1' => $row['report_bold1'],
            'report_formula2' => $row['report_formula2'],
            'report_operator2' => $row['report_operator2'],
            'report_type2' => $row['report_type2'],
            'report_tab2' => $row['report_tab2'],
            'report_bold2' => $row['report_bold2'],
            'report_formula3' => $row['report_formula3'],
            'report_operator3' => $row['report_operator3'],
            'balance_report_type' => $row['balance_report_type'] ?? 'default_value',
            'balance_report_type1' => $row['balance_report_type1'],
            'created_id' => $row['created_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'deleted_at' => $row['deleted_at'],
            'data_state' => $row['data_state'],
        ]);
    }
}
