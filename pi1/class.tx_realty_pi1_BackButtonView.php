<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class renders the back button.
 *
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_BackButtonView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the back button.
     *
     * @param array $piVars piVars array, may be empty
     *
     * @return string HTML for the back button, will not be empty
     */
    public function render(array $piVars = array())
    {
        if ($this->nextPreviousButtonsAreEnabled()) {
            $backUrl = $this->getBackLinkUrl();
            $javaScriptBack = '';
        } else {
            $backUrl =  '#';
            $javaScriptBack = ' onclick="history.back(); return false;"';
        }

        $this->setMarker('BACK_URL', $backUrl);
        $this->setMarker('JAVASCRIPT_BACK', $javaScriptBack);

        return $this->getSubpart('FIELD_WRAPPER_BACKBUTTON');
    }

    /**
     * Builds the URL for the back link.
     *
     * @return string the URL to the listView, will be empty if listUid is not
     *                set or zero in piVars
     */
    private function getBackLinkUrl()
    {
        if ((int)$this->piVars['listUid'] === 0) {
            return '';
        }

        $listUid = (int)$this->piVars['listUid'];

        try {
            $listViewPage = Tx_Oelib_Db::selectSingle(
                'pid',
                'tt_content',
                'uid=' . $listUid . Tx_Oelib_Db::enableFields('tt_content')
            );
        } catch (Tx_Oelib_Exception_EmptyQueryResult $exception) {
            return '';
        }

        $additionalParameters = array();
        if (isset($this->piVars['listViewLimitation'])) {
            $decodedParameters = json_decode($this->piVars['listViewLimitation'], true);
            $additionalParameters = (is_array($decodedParameters)) ? $decodedParameters : array();
        }

        $urlParameter = array(
            'parameter' => $listViewPage['pid'],
            'additionalParams' => GeneralUtility::implodeArrayForUrl(
                $this->prefixId, $additionalParameters
            ),
            'useCacheHash' => true,
        );

        return htmlspecialchars($this->cObj->typoLink_URL($urlParameter));
    }

    /**
     * Checks whether the display of the next/previous buttons is enabled.
     *
     * @return bool TRUE if the buttons should be displayed, FALSE otherwise
     */
    private function nextPreviousButtonsAreEnabled()
    {
        if (!isset($this->piVars['listUid'])) {
            return false;
        }
        if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
            return false;
        }

        $displayedSingleViewParts = GeneralUtility::trimExplode(
            ',', $this->getConfValueString('singleViewPartsToDisplay'), true
        );

        if (!in_array('nextPreviousButtons', $displayedSingleViewParts)) {
            return false;
        }

        return true;
    }
}
