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
 * This class renders the overview table view.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_OverviewTableView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns this view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for this view or an empty string if the realty object
     *                with the provided UID has no data to show
     */
    public function render(array $piVars = array())
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($piVars['showUid']);
        $objectNumber = htmlspecialchars($realtyObject->getProperty('object_number'));

        $hasObjectNumber = $this->setOrDeleteMarkerIfNotEmpty('object_number', $objectNumber, '', 'field_wrapper');
        $hasTableRows = $this->createTableRows($piVars['showUid']);

        return ($hasObjectNumber || $hasTableRows) ? $this->getSubpart('FIELD_WRAPPER_OVERVIEWTABLE') : '';
    }

    /**
     * Fills the subpart "OVERVIEW_ROW" with the contents of the current
     * record's database fields specified via the TS setup variable
     * "fieldsInSingleViewTable".
     *
     * @param int $uid UID of the realty object for which to create the table, must be > 0
     *
     * @return bool TRUE if at least one row has been filled, FALSE otherwise
     */
    private function createTableRows($uid)
    {
        $fieldNames = $this->getFieldNames($uid);
        if (empty($fieldNames)) {
            $this->hideSubparts('overview_row');
            return false;
        }

        $rows = array();
        $rowCounter = 0;
        /** @var tx_realty_pi1_Formatter $formatter */
        $formatter = GeneralUtility::makeInstance('tx_realty_pi1_Formatter', $uid, $this->conf, $this->cObj);

        foreach ($fieldNames as $key) {
            if ($this->setMarkerIfNotEmpty('data_current_row', $formatter->getProperty($key))) {
                $position = ($rowCounter % 2) ? 'odd' : 'even';
                $this->setMarker('class_position_in_list', $position);
                $this->setMarker('label_current_row', $this->translate('label_' . $key));
                $rows[] = $this->getSubpart('OVERVIEW_ROW');
                $rowCounter++;
            }
        }

        $this->setSubpart('overview_row', implode(LF, $rows));

        return ($rowCounter > 0);
    }

    /**
     * Returns the field names for which to create the overview table. They are
     * derived from the configuration in "fieldsInSingleViewTable".
     *
     * @param int $uid UID of the realty object, must be > 0
     *
     * @return string[] field names with which to fill the overview table, will be empty if none are configured
     */
    public function getFieldNames($uid)
    {
        if (!$this->hasConfValueString('fieldsInSingleViewTable')) {
            return array();
        }

        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($uid);

        if ($this->getConfValueBoolean('priceOnlyIfAvailable') && $realtyObject->isRentedOrSold()) {
            $fieldsToHideForThisType = array(
                'rent_excluding_bills', 'extra_charges', 'deposit', 'provision', 'buying_price', 'hoa_fee', 'year_rent',
                'rent_per_square_meter', 'garage_rent', 'garage_price'
            );
        } else {
            $fieldsToHideForThisType = array();
        }

        /** @var string[] $fieldNamesToShow */
        $fieldNamesToShow = array();
        /** @var string[] $fieldsConfiguredToShow */
        $fieldsConfiguredToShow = GeneralUtility::trimExplode(',', $this->getConfValueString('fieldsInSingleViewTable'), true);

        foreach ($fieldsConfiguredToShow as $key) {
            $fieldIsAllowed = $realtyObject->isAllowedKey($key);
            $fieldIsHidden = in_array($key, $fieldsToHideForThisType, true);
            if ($fieldIsAllowed && !$fieldIsHidden) {
                $fieldNamesToShow[] = $key;
            }
        }

        return $fieldNamesToShow;
    }
}
