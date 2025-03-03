<?php
// File: app/Utils/BranchUtils.php
namespace App\Utils;

use App\Models\CoreBranch;

class BranchUtils
{
    public static function getBranchName($branch_id)
    {
        $data = CoreBranch::where('branch_id', $branch_id)
                          ->where('data_state', 0)
                          ->first();

        return $data ? $data->branch_name : '';
    }
}

?>