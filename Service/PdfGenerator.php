<?php

namespace Peerj\Bundle\MpdfBundle\Service;

use mPDF;
use Symfony\Component\HttpFoundation\Response;

class PdfGenerator
{
    protected $mpdf;
    protected $renderer;
    protected $logger;
    protected $start_time;
    
    public function __construct($renderer, $logger)
    {
        $this->renderer = $renderer;
        $this->logger = $logger;
    }
    
    public function init($mode = '', $format = '', $default_font_size = '', $default_font = '', $margin_left = '', $margin_right = '', $margin_top = '', $margin_bottom = '', $margin_header = '', $margin_footer = '', $orientation = '')
    {
        $this->start_time = microtime(true);
        $this->mpdf = new mPDF($mode, $format, $default_font_size, $default_font, $margin_left, $margin_right, $margin_top, $margin_bottom, $margin_header, $margin_footer, $orientation);     
    }
    
    public function setHtml($html)
    {
       if (!$this->mpdf) {
          $this->init();
       }
       $this->mpdf->WriteHTML($html);
    }
    
    public function useTwigTemplate($template, array $data = array())
    {
        $this->logger->debug(print_r($data, true));
        $html = $this->renderer->render($template, $data);
        $this->setHtml($html);
    }

    public function generate()
    {
        if (!$this->mpdf) {
          $this->init();
        }

        // Better to avoid having mpdf set any headers as these can interfer with symfony responses
        $output = $this->mpdf->Output('', 'S');

        $time = microtime(true) - $this->start_time;
        $this->logger->debug("peerj_mpdf pdf generation took " . $time . " seconds");
        
        return $output;
    }

    public function generateInlineFileResponse($filename)
    {
        $headers = array(
                    'content-type' => 'application/pdf',
                    'content-disposition' => sprintf('inline; filename="%s"', $filename),
                    );
        
        $content = $this->generate();
        return new Response($content, 200, $headers);
    }
}
