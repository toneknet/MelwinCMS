<?php
//print "hey";
/*
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

$pdfPluginPath = rtrim(PLUGINPATH, '/\\');
if (!class_exists(\Mpdf\Mpdf::class)) {
  if (is_file($pdfPluginPath . '/mpdf/src/Mpdf.php')) {
    require_once __DIR__ . '/dependencies.cls.php';
    (new dependencies($pdfPluginPath))->load('mpdf');
  } else {
    foreach ([$pdfPluginPath . '/vendor/autoload.php', dirname($pdfPluginPath) . '/mpdf/vendor/autoload.php'] as $pdfAutoload) {
      if (is_file($pdfAutoload)) require_once $pdfAutoload;
      if (class_exists(\Mpdf\Mpdf::class)) break;
    }
  }
}
if (!class_exists(\Mpdf\Mpdf::class)) {
  throw new \RuntimeException('mPDF saknas. Öppna Beroenden i installations- eller uppdateringsverktyget och välj Hämta och installera.');
}
/**
 * Description of pdf
 * A wrapper for mpdf with added functionallity for footer and stylesheet
 *
 *  $pdf = new pdf();
 *  $pdf->setFileName("tidrapport_{$ym}_" . $this->arrUser['name']);
 *  $pdf->AddPage('L');
 *  $pdf->add_css(file_get_contents(__DIR__ . '/html/jobbtid.css'));
 *  $pdf->WriteHTML($html);
 *  $pdf->SetDisplayPreferences('/HideMenubar/HideToolbar/DisplayDocTitle');
 *  $pdf->create_file();
 * 
 * @author Martin Tonek <martin@tonek.se>
 */
class pdf extends \Mpdf\Mpdf
{
  private $pdfsettings = array();
  public $filename = '';

  /**
   * Add footer to pdf. Triggered by create_file
   */
  function add_footer()
  {
    //  $arrSettings = getCompanySettings();
    $arrSettings = settings::getSettings();
    //  if (!is_array($arrSettings)) {$arrSettings = unserialize($arrSettings);}
    //pre($arrSettings);
    if (isset($arrSettings['module']['system']['pdf_footer']) && is_file($arrSettings['module']['system']['pdf_footer'])) {
      $pdf_footer = file_get_contents($arrSettings['module']['system']['pdf_footer']);
      $this->SetHTMLFooter('<p style="font-size:8px;">genererad ' . date("Y-m-d H:i") . '</p>' . $pdf_footer);
    }
  }

  /**
   * Creates pdf file and saves it to the PDF map.
   * @param  boolean $addFooter                 Add footer automatic. Enabled as default
   * @param  boolean $showMsg                   Add message automatic. Enabled as default
   * @param  string  $strFilename               The filename to save as
   * @return [type]               [description]
   */
  function create_file($addFooter = true, $showMsg = true, $strFilename = 'test')
  {
    if ($strFilename == "test" && $this->filename <> "") {
      $strFilename = $this->filename;
    }
    $this->showImageErrors = true;
    $this->SetDisplayMode('fullpage');
    if ($addFooter) $this->add_footer();
    $filename = ROOTPATH . "pdf/" . $strFilename . ".pdf";
    if (file_exists($filename)) {
      @unlink($filename);
    }
    $GLOBALS['DEBUG'][][__FILE__] = $filename;
    $this->Output($filename, "F"); //I
    if ($showMsg) setMsg('n', "<a href=\"" . URLBASE . "/pdf/{$strFilename}.pdf\" class=\"pdf link\"  target=\"_blank\">Hämta pdf</a><br />");
    return "<a href=\"" . URLBASE . "/pdf/{$strFilename}.pdf\" class=\"pdf link\"  target=\"_blank\">Hämta pdf</a><br />";
  }

  /**
   * Add CSS as text here.
   * @param string $stylesheet  CSS styles as text here
   */
  function add_css($stylesheet = '')
  {
    $this->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
  }


  function setFileName($name)
  {
    return $this->filename = strtolower(str_replace(array(" ", "-"), array("_", "_"), $name));
  }

  /**
   * NOT USED!
   * @return [type] [description]
   */
  /*function test() {
      $html = '
      <html>
      <head>
      <style>
      body {font-family: sans-serif;
      	font-size: 10pt;
      }
      p {	margin: 0pt; }
      table.items {
      	border: 0.1mm solid #000000;
      }
      td { vertical-align: top; }
      .items td {
      	border-left: 0.1mm solid #000000;
      	border-right: 0.1mm solid #000000;
      }
      table thead td { background-color: #EEEEEE;
      	text-align: center;
      	border: 0.1mm solid #000000;
      	font-variant: small-caps;
      }
      .items td.blanktotal {
      	background-color: #EEEEEE;
      	border: 0.1mm solid #000000;
      	background-color: #FFFFFF;
      	border: 0mm none #000000;
      	border-top: 0.1mm solid #000000;
      	border-right: 0.1mm solid #000000;
      }
      .items td.totals {
      	text-align: right;
      	border: 0.1mm solid #000000;
      }
      .items td.cost {
      	text-align: "." center;
      }
      </style>
      </head>
      <body>

      <!--mpdf
      <htmlpageheader name="myheader">
      <table width="100%"><tr>
      <td width="50%" style="color:#0000BB; "><span style="font-weight: bold; font-size: 14pt;">Acme Trading Co.</span><br />123 Anystreet<br />Your City<br />GD12 4LP<br /><span style="font-family:dejavusanscondensed;">&#9742;</span> 01777 123 567</td>
      <td width="50%" style="text-align: right;">Invoice No.<br /><span style="font-weight: bold; font-size: 12pt;">0012345</span></td>
      </tr></table>
      </htmlpageheader>

      <htmlpagefooter name="myfooter">
      <div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
      Page {PAGENO} of {nb}
      </div>
      </htmlpagefooter>

      <sethtmlpageheader name="myheader" value="on" show-this-page="1" />
      <sethtmlpagefooter name="myfooter" value="on" />
      mpdf-->

      <div style="text-align: right">Date: 13th November 2008</div>

      <table width="100%" style="font-family: serif;" cellpadding="10"><tr>
      <td width="45%" style="border: 0.1mm solid #888888; "><span style="font-size: 7pt; color: #555555; font-family: sans;">SOLD TO:</span><br /><br />345 Anotherstreet<br />Little Village<br />Their City<br />CB22 6SO</td>
      <td width="10%">&nbsp;</td>
      <td width="45%" style="border: 0.1mm solid #888888;"><span style="font-size: 7pt; color: #555555; font-family: sans;">SHIP TO:</span><br /><br />345 Anotherstreet<br />Little Village<br />Their City<br />CB22 6SO</td>
      </tr></table>

      <br />

      <table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse; " cellpadding="8">
      <thead>
      <tr>
      <td width="15%">Ref. No.</td>
      <td width="10%">Quantity</td>
      <td width="45%">Description</td>
      <td width="15%">Unit Price</td>
      <td width="15%">Amount</td>
      </tr>
      </thead>
      <tbody>
      <!-- ITEMS HERE -->
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <tr>
      <td align="center">MF1234567</td>
      <td align="center">10</td>
      <td>Large pack Hoover bags</td>
      <td class="cost">&pound;2.56</td>
      <td class="cost">&pound;25.60</td>
      </tr>
      <tr>
      <td align="center">MX37801982</td>
      <td align="center">1</td>
      <td>Womans waterproof jacket<br />Options - Red and charcoal.</td>
      <td class="cost">&pound;102.11</td>
      <td class="cost">&pound;102.11</td>
      </tr>
      <tr>
      <td align="center">MR7009298</td>
      <td align="center">25</td>
      <td>Steel nails; oval head; 30mm x 3mm. Packs of 1000.</td>
      <td class="cost">&pound;12.26</td>
      <td class="cost">&pound;325.60</td>
      </tr>
      <!-- END ITEMS HERE -->
      <tr>
      <td class="blanktotal" colspan="3" rowspan="6"></td>
      <td class="totals">Subtotal:</td>
      <td class="totals cost">&pound;1825.60</td>
      </tr>
      <tr>
      <td class="totals">Tax:</td>
      <td class="totals cost">&pound;18.25</td>
      </tr>
      <tr>
      <td class="totals">Shipping:</td>
      <td class="totals cost">&pound;42.56</td>
      </tr>
      <tr>
      <td class="totals"><b>TOTAL:</b></td>
      <td class="totals cost"><b>&pound;1882.56</b></td>
      </tr>
      <tr>
      <td class="totals">Deposit:</td>
      <td class="totals cost">&pound;100.00</td>
      </tr>
      <tr>
      <td class="totals"><b>Balance due:</b></td>
      <td class="totals cost"><b>&pound;1782.56</b></td>
      </tr>
      </tbody>
      </table>


      <div style="text-align: center; font-style: italic;">Payment terms: payment due in 30 days</div>


      </body>
      </html>
      ';

      //$path = (getenv('MPDF_ROOT')) ? getenv('MPDF_ROOT') : __DIR__;
      require_once str_replace("plugins/","mpdf",PLUGINPATH) . '/vendor/autoload.php';

      $mpdf = new \Mpdf\Mpdf([
      	'margin_left' => 20,
      	'margin_right' => 15,
      	'margin_top' => 48,
      	'margin_bottom' => 25,
      	'margin_header' => 10,
      	'margin_footer' => 10
      ]);

      $mpdf->SetProtection(array('print'));
      $mpdf->SetTitle("Acme Trading Co. - Invoice");
      $mpdf->SetAuthor("Acme Trading Co.");
      $mpdf->SetWatermarkText("Paid");
      $mpdf->showWatermarkText = true;
      $mpdf->watermark_font = 'DejaVuSansCondensed';
      $mpdf->watermarkTextAlpha = 0.1;
      $mpdf->SetDisplayMode('fullpage');

      $mpdf->WriteHTML($html);

      $strFilename = "0001_test";
      //setMsg('n','Create PDF');
      $filename=ROOTPATH ."pdf/".$strFilename.".pdf";
      if (file_exists($filename)) {
        @unlink($filename);
      }
      //setMsg('n','MPDF: ' . $filename);
      $GLOBALS['DEBUG'][][__FILE__] = $filename;
      $mpdf->Output($filename,"F");//I
  }*/
}
