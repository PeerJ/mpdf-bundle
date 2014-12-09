<?php
namespace Peerj\Bundle\MpdfBundle\Service;

use mPDF;
use Symfony\Component\HttpFoundation\Response;

class PdfGenerator
{
    /**
     * @var mPDF
     */
    protected $mpdf;

    /**
     * @var \Symfony\Bridge\Twig\TwigEngine;
     */
    protected $renderer;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @var float
     */
    protected $start_time;

    /**
     * @var boolean
     */
    protected $isInitialized = false;

    /**
     * @param $renderer
     * @param $logger
     * @param $cache_dir
     */
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

        if (!defined('_MPDF_TEMP_PATH')) {
            define("_MPDF_TEMP_PATH", $tmp_folder);
        }
        if (!defined('_MPDF_TTFONTDATAPATH')) {
            define("_MPDF_TTFONTDATAPATH", $font_folder);
        }

        $this->renderer = $renderer;
        $this->logger = $logger;
    }

    /*
     * Initialize Mpdf
     * Would expect this to be called from DI
     *
     * @param array $additionalFonts
     */
    public function initMpdf($additionalFonts = array())
    {
        $this->mpdf = new mPDF($additionalFonts);
    }

    /*
     * Copy font files to _MPDF_TTFONTPATH
     * Shame that _MPDF_SYSTEM_TTFONTS only allows 1 folder
     *
     * @param array $fontFiles expected array(array('path'=>'','file'=>''),...)
     */
    public function copyFontFiles($fontFiles = array())
    {
        if (!defined(_MPDF_TTFONTPATH)) {
            $tmpMpdf = new mPDF();
        }

        foreach(array_values($fontFiles) as $fontFile) {
            $source = sprintf("%s/%s", $fontFile['path'], $fontFile['file']);
            $dest = sprintf("%s%s", _MPDF_TTFONTPATH, $fontFile['file']);
            if (!file_exists($dest)) {
                copy($source, $dest);
                $this->logger->debug(sprintf("Copying font file %s to %s", $source, $dest));
            }
        }
    }

    /**
     * @param string $mode
     * @param string $format
     * @param string $default_font_size
     * @param string $default_font
     * @param string $margin_left
     * @param string $margin_right
     * @param string $margin_top
     * @param string $margin_bottom
     * @param string $margin_header
     * @param string $margin_footer
     * @param string $orientation
     * @see mPDF
     */
    public function init($mode = '', $format = '', $default_font_size = '', $default_font = '', $margin_left = '', $margin_right = '', $margin_top = '', $margin_bottom = '', $margin_header = '', $margin_footer = '', $orientation = '')
    {
        $this->start_time = microtime(true);
        if (!$this->mpdf) {
            $this->initMpdf();
        }

        $this->mpdf->mPDF($mode, $format, $default_font_size, $default_font, $margin_left, $margin_right, $margin_top, $margin_bottom, $margin_header, $margin_footer, $orientation);
        $this->isInitialized = true;

        $this->logger->debug("peerj_mpdf: Using temp folder " . _MPDF_TEMP_PATH);
        $this->logger->debug("peerj_mpdf: Using font folder " . _MPDF_TTFONTDATAPATH);
    }

    /**
     * Set property of mPDF
     * @param $name
     * @param $value
     */
    public function setProperty($name, $value)
    {
        $this->mpdf->{$name} = $value;
    }

    /**
     * Call method of mPDF
     * @param $name
     * @param array $data
     */
    public function callMethod($name, array $data)
    {
        call_user_func_array(array($this->mpdf, $name), $data);
    }

    /**
     * Get instance of mPDF object
     * @return mPDF
     */
    public function getMpdf()
    {
        return $this->mpdf;
    }

    /**
     * Set html
     * @param string $html
     */
    public function setHtml($html, $useSubstitutions = false)
    {
        if (!$this->isInitialized) {
            $this->init();
        }
        // If using substitutions, must be set prior to WriteHTML
        $this->mpdf->useSubstitutions = $useSubstitutions;
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Renders and set as html the template with the given context
     * @param $template
     * @param array $data
     */
    public function useTwigTemplate($template, array $data = array(), $useSubstitutions = false)
    {
        $html = $this->renderer->render($template, $data);
        $this->setHtml($html, $useSubstitutions);

        return $this;
    }

    /**
     * Generate pdf document and return it as string
     * @return string
     */
    public function generate()
    {
        if (!$this->isInitialized) {
            $this->init();
        }

        // Better to avoid having mpdf set any headers as these can interfer with symfony responses
        $output = $this->mpdf->Output('', 'S');

        $time = microtime(true) - $this->start_time;
        $this->logger->debug("peerj_mpdf: pdf generation took " . $time . " seconds");

        return $output;
    }

    /**
     * Generate pdf document and returns it as Response object
     * @param $filename
     * @return Response
     */
    public function generateInlineFileResponse($filename)
    {
        $headers = array(
            'content-type' => 'application/pdf',
            'content-disposition' => sprintf('inline; filename="%s"', $filename),
        );

        $content = $this->generate();

        return new Response($content, 200, $headers);
    }

    /**
     * Return mPDF version
     * @return null|string
     */
    public function getVersion()
    {
        return defined('mPDF_VERSION') ? mPDF_VERSION : null;
    }
}
