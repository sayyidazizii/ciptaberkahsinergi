<?php
// app/Imports/ProfitLossImport.php

// app/Imports/ProfitLossImport.php

namespace App\Imports;

use App\Models\MigrationProfitLoss;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProfitLossImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new MigrationProfitLoss([
            'profit_loss_report_id' => $row['profit_loss_report_id'],
            'format_id' => $row['format_id'],
            'report_no' => $row['report_no'],
            'account_type_id' => $row['account_type_id'],
            'account_id' => $row['account_id'],
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'account_amount_migration' => $row['account_amount_migration'],
            'report_formula' => $row['report_formula'],
            'report_operator' => $row['report_operator'],
            'report_type' => $row['report_type'],
            'report_tab' => $row['report_tab'],
            'report_bold' => $row['report_bold'],
            'created_id' => $row['created_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'deleted_at' => $row['deleted_at'],
            'data_state' => $row['data_state'],
        ]);
    }
}
