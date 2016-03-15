<?php
namespace AppBundle\Export\Sheet;


use AppBundle\Entity\Event;
use AppBundle\Entity\User;

abstract class AbstractSheet
{


    /**
     * Current sheet
     *
     * @var \PHPExcel_Worksheet
     */
    protected $sheet;

    /**
     * Current column index
     *
     * @var integer
     */
    protected $column = 0;

    /**
     * Maximum of column index
     *
     * @var integer
     */
    protected $columnMax = 0;

    /**
     * Current row index
     *
     * @var integer
     */
    protected $row = 1;

    /**
     * Maximum of row index
     *
     * @var integer
     */
    protected $rowMax = 1;

    /**
     * Stores the row index of the header line
     *
     * @var integer
     */
    protected $rowHeaderLine = null;


    public function __construct(\PHPExcel_Worksheet $sheet)
    {
        $this->sheet = $sheet;
    }

    /**
     * Get the current column index
     *
     * @param  int|null $index    If set to null, the current column index will be used. If a integer is defined,
     *                            the transmitted value will be used and the index
     * @param  bool     $increase If set to true, the current column index returned but the index is increased
     * @return int                Get the current column index
     */
    protected function column($index = null, $increase = true)
    {
        if ($index === null) {
            $column = $this->column;

            if ($increase) {
                ++$this->column;
            }
        } else {
            $column = $index;

            if ($increase) {
                $this->column = $column + 1;
            }
        }


        if ($this->column > $this->columnMax) {
            $this->columnMax = $this->column;
        }

        return $column;
    }

    /**
     * Get the current row index
     *
     * @param  int|null $index    If set to null, the current row index will be used. If a integer is defined,
     * @param bool      $increase If set to true, the current row index returned but the index is increased
     * @return int           Get the current row index
     */
    protected function row($index = null, $increase = true)
    {
        if ($index === null) {
            $row = $this->row;

            if ($increase) {
                ++$this->row;
            }
        } else {
            $row = $index;

            if ($increase) {
                $this->row = $row + 1;
            }
        }

        if ($this->row > $this->rowMax) {
            $this->rowMax = $this->row;
        }

        return $row;
    }

    /**
     * Set the header of this sheet
     *
     * @return $this
     */
    public function setHeader($title = null, $subtitle = null)
    {
        $sheet = $this->sheet;

        $column = $this->column();
        $sheet->getColumnDimensionByColumn($column)
              ->setWidth(3);

        $row    = null;
        $column = $this->column();

        if ($title) {
            $row = $this->row();

            $sheet->setCellValueByColumnAndRow($column, $row, $title);
            $sheet->getStyleByColumnAndRow($column, $row)
                  ->getFont()
                  ->setBold(true)
                  ->setName('Arial')
                  ->setSize(13)
                  ->getColor()
                  ->setRGB('1C639E');
            $sheet->getRowDimension($row)
                  ->setRowHeight(26);
        }

        if ($subtitle) {
            $row = $this->row();

            $sheet->setCellValueByColumnAndRow($column, $row, $subtitle);
            $sheet->getStyleByColumnAndRow($column, $row)
                  ->getFont()
                  ->setBold(true)
                  ->setName('Arial')
                  ->setSize(19)
                  ->getColor()
                  ->setRGB('262626');
            $sheet->getRowDimension($row)
                  ->setRowHeight(24);
        }

        if ($row === null) {
            $row = 0;
        }
        $row += 2;

        $this->rowHeaderLine = $this->row($row);
        $sheet->getRowDimension($row)
              ->setRowHeight(5);

        $row = $this->row();
        $sheet->getRowDimension($row)
              ->setRowHeight(34);

        return $this;
    }

    /**
     * Set the main content of this sheet
     *
     * @return $this
     */
    public abstract function setBody();

    /**
     * Set the footer of this sheet if any
     *
     * @return $this
     */
    public function setFooter()
    {
        $sheet  = $this->sheet;
        $row    = $this->rowHeaderLine;
        $column = $this->column($this->columnMax);

        $sheet->getColumnDimensionByColumn($column)
              ->setWidth(3);

        $sheet->getStyleByColumnAndRow(0, $row, $column, $row)
              ->applyFromArray(
                  array(
                      'fill' => array(
                          'type'  => \PHPExcel_Style_Fill::FILL_SOLID,
                          'color' => array('rgb' => '1C639E')
                      )
                  )
              );

        return $this;
    }

    /**
     * Process sheet data
     *
     * @return $this
     */
    public function process()
    {
        $this->setHeader(null, null);
        $this->setBody();
        $this->setFooter();

        return $this;
    }

}