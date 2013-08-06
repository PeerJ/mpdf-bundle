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
    
    public function __construct($renderer, $logger, $cache_dir)
    {
        // vendor folder probably doesn't have write access,
        // so put the temp folder under the cache folder which should have write access
        $tmp_folder = $cache_dir . '/tmp/';
        if (!is_dir($tmp_folder)) {
          mkdir($tmp_folder);
        }

        $font_folder = $cache_dir . '/ttfontdata/';
        if (!is_dir($font_folder)) {
          mkdir($font_folder);
        }

        if (!defined('_MPDF_TEMP_PATH')) { define("_MPDF_TEMP_PATH", $tmp_folder); }
        if (!defined('_MPDF_TTFONTDATAPATH'))  { define("_MPDF_TTFONTDATAPATH", $font_folder); }
        
        $this->renderer = $renderer;
        $this->logger = $logger;
    }
    
    public function init($mode = '', $format = '', $default_font_size = '', $default_font = '', $margin_left = '', $margin_right = '', $margin_top = '', $margin_bottom = '', $margin_header = '', $margin_footer = '', $orientation = '')
    {
        $this->start_time = microtime(true);
        $this->mpdf = new mPDF($mode, $format, $default_font_size, $default_font, $margin_left, $margin_right, $margin_top, $margin_bottom, $margin_header, $margin_footer, $orientation);

        $this->logger->debug("peerj_mpdf: Using temp folder " . _MPDF_TEMP_PATH);
        $this->logger->debug("peerj_mpdf: Using font folder " . _MPDF_TTFONTDATAPATH);
    }
    
    public function setProperty($name, $value)
    {
        $this->mpdf->{$name} = $value;
    }
    
    public function callMethod($name, array $data)
    {
        call_user_func_array(array($this->mpdf, $name), $data);
    }
    
    public function getMpdf()
    {
        return $this->mpdf;
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
        $this->logger->debug("peerj_mpdf: pdf generation took " . $time . " seconds");
        
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
