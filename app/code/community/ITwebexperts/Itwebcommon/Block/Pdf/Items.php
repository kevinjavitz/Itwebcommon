<?php

/**
 * @author     Kristof Ringleff
 * @package    Fooman_PdfCustomiser
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ITwebexperts_Itwebcommon_Block_Pdf_Items extends ITwebexperts_Itwebcommon_Block_Pdf_Abstract
{

    /**
     * retrieve column headers from pdf helper object
     *
     * @return array|bool
     */
    public function getColumnHeaders ()
    {
        return $this->getPdfHelper()->getPdfColumnHeaders();
    }

    /**
     * retrieve item html from pdf helper object
     *
     * @param array       $pdfItem       individual pdf item as prepared by Mypdf
     * @param string      $vertSpacing   tcpdf instructions for vertical spacing
     * @param bool|string $styleOverride basic css to override style of line
     * @param int         $position      item counter
     *
     * @return string
     */
    public function getItemRow($pdfItem, $vertSpacing, $styleOverride = false, $position = 1)
    {
        return $this->getPdfHelper()->getPdfItemRow(
            $pdfItem,
            $vertSpacing,
            $styleOverride,
            $position
        );
    }

    /**
     * retrieve item html for bundle item from pdf helper object
     *
     * @param array       $pdfItem       individual pdf item as prepared by Mypdf
     * @param array       $subItems      array of bundle pdf items
     * @param string      $vertSpacing   tcpdf instructions for vertical spacing
     * @param bool|string $styleOverride basic css to override style of line
     * @param int         $position      item counter
     *
     * @return string
     */
    public function getBundleItemRow($pdfItem, $subItems, $vertSpacing, $styleOverride = false, $position = 1)
    {
        return $this->getPdfHelper()->getPdfBundleItemRow(
            $pdfItem,
            $subItems,
            $vertSpacing,
            $styleOverride,
            $position
        );
    }

    /**
     * retrieve pdf items from pdf object
     *
     * @return array
     */
    public function getItems()
    {
        return $this->getPdf()->getItems();
    }

    /**
     * retrieve pdf bundle items from pdf object
     *
     * @return array
     */
    public function getBundleItems()
    {
        return $this->getPdf()->getBundleItems();
    }

}