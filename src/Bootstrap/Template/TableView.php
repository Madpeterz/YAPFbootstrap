<?php

namespace YAPF\Bootstrap\Template;

class TableView extends BasicView
{
    protected bool $tableResponsive = true;
    public function setTableResponsive(bool $status): void
    {
        $this->tableResponsive = $status;
    }
    public function renderTable(
        array $table_head,
        array $table_body,
        string $classaddon = "",
        bool $show_head = true
    ): string {
        $output = '<table class="' . $classaddon . ' table table-striped">';
        if ($this->tableResponsive == true) {
            $output = '<div class="table-responsive">' . $output;
        }
        if ($show_head == true) {
            $output .= '<thead><tr>';
            foreach ($table_head as $entry) {
                $output .= '<th scope="col">' . $entry . '</th>';
            }
            $output .= '</tr></thead>';
        }

        $output .= '<tbody>';
        foreach ($table_body as $row) {
            if (is_array($row) == true) {
                $output .= "<tr>";
                foreach ($row as $entry) {
                    $output .= "<td>" . $entry . "</td>";
                }
                $output .= "</tr>";
            }
        }
        $output .= '</tbody>';
        $output .= '</table>';
        if ($this->tableResponsive == true) {
            $output .= "</div>";
        }
        return $output;
    }
}
