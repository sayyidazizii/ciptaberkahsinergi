<?php

namespace App\Livewire;

use Cst\WALaravel\WA;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class WaScan extends Component
{
    public $wa;
    public $error;
    public $success;
    public $dump;
    public $to;
    public $msg;
    public function render()
    {
        $request = WA::qr();
        if($request->object()){
            $qr = $request->object();
        }else{
            $qr = $request->body();
        }
        return view('livewire.wa-scan',compact('qr'));
    }
    public function getQr(){
        $request = WA::qr();
        if($request->object()){
            $this->wa = $request->object();
        }else{
            $this->wa = $request->body();
        }
    }
    public function getQrSilent(){
        $request = WA::qr();
        if($request->object()){
            $this->wa = $request->object();
        }else{
            $this->wa = $request->body();
        }
    }
    public function sendMessage(){
        if(empty($this->to) || empty($this->msg)){
            $this->error = "Penerima dan pesan tidak boleh kosong";
            return;
        }
        try{
            $request = WA::to($this->to)->msg($this->msg);
            if(json_decode($request)->message_status=="Success"){
                $this->success = "Pesan berhasil dikirim";
            }else{
                Log::error($request);
                $this->error = "Pesan gagal dikirim";
            }
        }catch(\Exception $e){
            $this->error = $e->getMessage();
            return;
        }
    }
}
