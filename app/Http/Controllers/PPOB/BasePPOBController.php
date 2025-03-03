<?php

namespace App\Http\Controllers\PPOB;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BasePPOBController extends Controller
{
    protected $maintenance = false;
    protected $PPOBmaintenance = false;
    protected $minSaldo = 25000;
    protected $maxTransaction = 500000;
    protected $maxTotalTransaction = 500000;
    /**
     * Minimal saldo yang diperlukan di simpanan
     * @return int
     */
    protected function minSaldo(){
        // TODO ambil min saldo dari setting database
        return $this->minSaldo;
    }
    /**
     * Maksimal transaksi (Rp)
     * @return int
     */
    protected function maxTransaction(){
        // TODO ambil min saldo dari setting database
        return$this->maxTransaction;
    }
    /**
     *  Maximal total transaksi per user
     * @return mixed
     */
    protected function maxTransactionPerUser(){
        // TODO ambil min saldo dari setting database
        return$this->maxTransaction;
    }

    /**
     * Get the value of maintenance
     */
    public function isMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * Get the value of PPOBmaintenance
     */
    public function isPPOBmaintenance()
    {
        return $this->PPOBmaintenance;
    }
}
