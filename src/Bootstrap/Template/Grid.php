<?php

namespace YAPF\Bootstrap\Template;

class Grid
{
    protected $col_value = 0;
    protected $row_open = false;
    protected $col_open = false;
    protected $output = "";
    protected $autoRowTrigger = true;

    public function disableAutoRow(): void
    {
        $this->autoRowTrigger = false;
    }

    public function getOutput(): string
    {
        $this->closeCol();
        $this->closeRow();
        if ($this->output == null) {
            $this->output = "";
        }
        return $this->output;
    }
    public function addBefore(string $content): void
    {
        $this->output = $content . "" . $this->output;
    }
    public function addAfter(string $content): void
    {
        $this->output .= $content;
    }
    public function addContent(string $content, int $size = 0, bool $center = false, bool $use_lookup_table = true): void
    {
        if ($size > 0) {
            $this->col($size, $center, $use_lookup_table);
        }
        $this->output .= $content;
    }
    public function col(int $size, bool $center = false, bool $use_lookup_table = true): void
    {
        $this->closeCol();
        if (($this->col_value + $size) > 12) {
            if ($this->autoRowTrigger == true) {
                $this->closeRow();
            }
        }
        if ($this->row_open == false) {
            $this->row($center);
        }
        $this->col_value += $size;
        $this->col_open = true;

        $lookup = [
            12 => [12,12,12,12,12],
            11 => [12,12,11,11,11],
            10 => [12,12,11,10,10],
            9 => [12,12,12,9,9],
            8 => [12,12,12,8,8],
            7 => [12,12,12,7,7],
            6 => [6,6,6,6,6],
            5 => [6,6,6,5,5],
            4 => [6,6,4,4,4],
            3 => [6,6,4,3,3],
            2 => [6,6,2,2,2],
            1 => [6,6,4,1,1],
        ];
        $chart = [$size,$size,$size,$size,$size];
        if ($use_lookup_table == true) {
            $chart = $lookup[$size];
        }

        $sizeChart = [
            "col-" . $chart[0],
            "col-sm-" . $chart[1],
            "col-md-" . $chart[2],
            "col-lg-" . $chart[3],
            "col-xl-" . $chart[4],
        ];

        $this->output .= '<div class="grid-margin ' . implode(" ", $sizeChart) . '">';
    }
    public function closeRow(): void
    {
        $this->closeCol();
        if ($this->row_open == true) {
            $this->row_open = false;
            $this->col_value = 0;
            $this->output .= '</div>';
        }
    }
    protected function closeCol(): void
    {
        if ($this->col_open == true) {
            $this->col_open = false;
            $this->output .= '</div>';
        }
    }
    protected function row(bool $centered): void
    {
        $this->closeRow();
        $addon = "";
        if ($centered == true) {
            $addon = " justify-content-md-center";
        }
        $this->output .= '<div class="row' . $addon . '">';
        $this->row_open = true;
    }
}
