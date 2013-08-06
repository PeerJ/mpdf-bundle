<?php

namespace Peerj\Bundle\MpdfBundle\Service;

use mPDF;

class Mpdf
{
    protected $mpdf;
    
    public function __construct()
    {
        $this->mpdf = new mPDF();
    }

    public function WriteHTML($html)
    {
        $this->mpdf->WriteHTML($html);
    }
    
    public function Output($file, $format)
    {
        $this->mpdf->Output();
    }
}
