<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\Participation;

class ParticipationsSheet extends AbstractSheet
{

    /**
     * The event this participation export belongs to
     *
     * @var Event
     */
    protected $event;

    /**
     * Stores a list of Participation entities
     *
     * @var array
     */
    protected $participations;


    public function __construct(\PHPExcel_Worksheet $sheet, Event $event, array $participations)
    {
        $this->event          = $event;
        $this->participations = $participations;

        parent::__construct($sheet);

        $this->addColumn(new EntitySheetColumn('salution', 'Anrede'));
        $this->addColumn(new EntitySheetColumn('nameFirst', 'Vorname'));
        $this->addColumn(new EntitySheetColumn('nameLast', 'Nachname'));

        $this->addColumn(new EntitySheetColumn('addressStreet', 'Straße (Anschrift)'));
        $this->addColumn(new EntitySheetColumn('addressCity', 'Stadt (Anschrift)'));
        $this->addColumn(new EntitySheetColumn('addressZip', 'PLZ (Anschrift)'));

        $this->addColumn(new EntitySheetColumn('email', 'E-Mail'));

        $this->addColumn(
            EntityPhoneNumberSheetColumn::createCommaSeparated('phoneNumbers', 'Telefonnummern', null, true)
        );

        $column = new EntitySheetColumn('createdAt', 'Eingang');
        $column->setNumberFormat('dd.mm.yyyy h:mm');
        $column->setConverter(
            function (\DateTime $value, $entity) {
                return \PHPExcel_Shared_Date::FormattedPHPToExcel(
                    $value->format('Y'), $value->format('m'), $value->format('d'),
                    $value->format('H'), $value->format('i')
                );
            }
        );
        $column->setWidth(14);
        $this->addColumn($column);

        $column = new EntitySheetColumn('participants', 'Teilnehmer');
        $column->setConverter(
            function ($value, $entity) {
                return count($value);
            }
        );
        $this->addColumn($column);

        $this->addColumn(new EntitySheetColumn('pid', 'PID'));

    }

    public function setHeader($title = null, $subtitle = null)
    {
        parent::setHeader($this->event->getTitle(), 'Anmeldungen');
        parent::setColumnHeaders();
    }

    public function setBody()
    {

        /** @var Participation $participation */
        foreach ($this->participations as $participation) {
            $row = $this->row();

            /** @var EntitySheetColumn $column */
            foreach ($this->columnList as $column) {
                $columnIndex = $column->getColumnIndex();
                $cellStyle   = $this->sheet->getStyleByColumnAndRow($columnIndex, $row);

                $column->process($this->sheet, $row, $participation);

                $columnDataConditional = $column->getDataCellConditionals();
                if (count($columnDataConditional)) {
                    $cellStyle->setConditionalStyles($columnDataConditional);
                }
                $cellStyle->getAlignment()->setVertical(
                    \PHPExcel_Style_Alignment::VERTICAL_TOP
                );
                $cellStyle->getBorders()->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

                $columnStyles = $column->getDataStyleCallbacks();
                if (count($columnStyles)) {
                    foreach ($columnStyles as $columnStyle) {
                        if (!is_callable($columnStyle)) {
                            throw new \InvalidArgumentException('Defined column style callback is not callable');
                        }
                        $columnStyle($cellStyle);
                    }
                }
            }
        }
    }

}