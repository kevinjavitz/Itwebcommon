<?php

/**
 * @author     Kristof Ringleff
 * @package    Fooman_PdfCustomiser
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class ITwebexperts_Itwebcommon_Block_Pdf_Abstract extends Mage_Core_Block_Template
{

    private $_pdfObject = null;

    /**
     * set current pdf object
     *
     * @param ITwebexperts_Itwebcommon_Model_Mypdf $pdf current pdf object
     *
     * @return Fooman_PdfCustomiser_Block_Pdf_Abstract
     * @access public
     */
    public function setPdf(ITwebexperts_Itwebcommon_Model_Mypdf $pdf)
    {
        $this->_pdfObject = $pdf;
        return $this;
    }

    /**
     * retrieve current pdf object
     *
     * @return ITwebexperts_Itwebcommon_Model_Mypdf
     * @access public
     */
    public function getPdf()
    {
        return $this->_pdfObject;
    }

    private $_pdfHelper = null;

    /**
     * set current pdf helper object
     *
     * @param ITwebexperts_Itwebcommon_Helper_Pdf $helper
     *
     * @return Fooman_PdfCustomiser_Block_Pdf_Abstract
     * @access public
     */
    public function setPdfHelper(ITwebexperts_Itwebcommon_Helper_Pdf $helper)
    {
        $this->_pdfHelper = $helper;
        return $this;
    }

    /**
     * retrieve current pdf helper object
     *
     * @return ITwebexperts_Itwebcommon_Helper_Pdf
     * @access public
     */
    public function getPdfHelper()
    {
        if (is_null($this->_pdfHelper)) {
            return Mage::helper('itwebcommon/pdfhelper');
        }
        return $this->_pdfHelper;
    }

    /*
     * @access private
     */
    private $_totals = null;

    /**
     * set sorted pdf totals
     *
     * @param array $totals array of totals for output in pdf
     *
     * @return Fooman_PdfCustomiser_Block_Pdf_Abstract
     * @access public
     */
    public function setTotals(array $totals)
    {
        $this->_totals = $totals;
        return $this;
    }

    /**
     * retrieve sorted totals
     *
     * @return array
     * @access public
     */
    public function getTotals()
    {
        return $this->_totals;
    }

    /**
     * $lineStyleOverrideprepare parameters for use with tcpdfs fake html tag
     *
     * @param $params
     *
     * @return string
     */
    public function serializeParams($params)
    {
        return $this->getPdfHelper()->serializeParams($params);
    }

}