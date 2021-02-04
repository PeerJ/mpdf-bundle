<?php
namespace Peerj\Bundle\MpdfBundle\Service;

use Mpdf\Config;
use Mpdf\Mpdf;

use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Response;

class PdfGenerator
{
    /**
     * @var TwigEngine
     */
    protected $renderer;

    /** @var string */
    protected $tmpFolder;

    /** @var array */
    protected $additionalFontData;

    /** @var array */
    protected $additionalFontPaths;

    /** @var string */
    protected $format;

    /**
     * @param $renderer
     */
    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @param array $additionalFonts
     */
    public function initMpdf($additionalFonts, $format, $tmpFolder)
    {
        $this->format = $format;
        if (!is_dir($tmpFolder)) {
            mkdir($tmpFolder);
        }
        $this->tmpFolder = $tmpFolder;

        $this->additionalFontData = [];
        $this->additionalFontPaths = [];

        foreach ($additionalFonts as $name => $info) {
            $this->additionalFontData[$name] = $info['data'];
            $this->additionalFontPaths[] = $info['path'];
        }
    }

    public function startPdf() {
        $defaultConfig = (new Config\ConfigVariables())->getDefaults();
        $fontDirs = array_merge($defaultConfig['fontDir'], $this->additionalFontPaths);

        $defaultFontConfig = (new Config\FontVariables())->getDefaults();
        $fontData = array_merge($defaultFontConfig['fontdata'], $this->additionalFontData);

        return new Mpdf([
            'fontDir' => $fontDirs,
            'fontData' => $fontData,
            'tempDir' => $this->tmpFolder,
            'format' => $this->format,
        ]);
    }

    /**
     * Set property of mPDF
     * @param Mpdf   $pdf
     * @param string $name
     * @param string $value
     */
    public function setProperty(Mpdf $pdf, $name, $value)
    {
        $pdf->{$name} = $value;
    }

    /**
     * Call method of mPDF
     * @param Mpdf   $pdf
     * @param string $name
     * @param array  $data
     */
    public function callMethod(Mpdf $pdf, $name, array $data = [])
    {
        call_user_func_array(array($pdf, $name), $data);
    }

    /**
     * Set html
     * @param Mpdf   $pdf
     * @param string $html
     * @param bool   $useSubstitutions
     *
     */
    public function setHtml(Mpdf $pdf, $html, $useSubstitutions = false)
    {
        // If using substitutions, must be set prior to WriteHTML
        $pdf->useSubstitutions = $useSubstitutions;
        $pdf->WriteHTML($html);
    }

    /**
     * Renders and set as html the template with the given context
     * @param Mpdf   $pdf
     * @param string $template
     * @param array  $data
     */
    public function useTwigTemplate(Mpdf $pdf, $template, array $data = array(), $useSubstitutions = false)
    {
        $html = $this->renderer->render($template, $data);
        $this->setHtml($pdf, $html, $useSubstitutions);

        return $pdf;
    }

    /**
     * Generate pdf document and return it as string
     * @param Mpdf $pdf
     * @return string
     */
    public function generate(Mpdf $pdf)
    {
        // Better to avoid having mpdf set any headers as these can interfer with symfony responses
        return $pdf->Output('', 'S');
    }

    /**
     * Generate pdf document and returns it as Response object
     * @param Mpdf $pdf
     * @param $filename
     * @return Response
     */
    public function generateInlineFileResponse(Mpdf $pdf, $filename)
    {
        $headers = array(
            'content-type' => 'application/pdf',
            'content-disposition' => sprintf('inline; filename="%s"', $filename),
        );

        $content = $this->generate($pdf);

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
