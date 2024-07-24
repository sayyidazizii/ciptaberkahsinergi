<?php
// app/Imports/AccountsImport.php

namespace App\Imports;

use App\Models\MigrationAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new MigrationAccount([
            'account_id' => $row['account_id'],
            'branch_id' => $row['branch_id'],
            'account_type_id' => $row['account_type_id'],
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'account_group' => $row['account_group'],
            'account_suspended' => $row['account_suspended'],
            'parent_account_id' => $row['parent_account_id'],
            'top_parent_account_id' => $row['top_parent_account_id'],
            'account_has_child' => $row['account_has_child'],
            'opening_debit_balance' => $row['opening_debit_balance'],
            'opening_credit_balance' => $row['opening_credit_balance'],
            'debit_change' => $row['debit_change'],
            'credit_change' => $row['credit_change'],
            'account_default_status' => $row['account_default_status'],
            'account_remark' => $row['account_remark'],
            'account_status' => $row['account_status'],
            'created_id' => $row['created_id'],
            'created_at' => $row['created_at'],
            'updated_id' => $row['updated_id'],
            'updated_at' => $row['updated_at'],
            'deleted_id' => $row['deleted_id'],
            'deleted_at' => $row['deleted_at'],
            'data_state' => $row['data_state'],
        ]);
    }
}

