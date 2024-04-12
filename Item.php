<?php


class Item
{
    private $name;
    private $price;
    private $tax;
    private $quantity;
    private $type;

    public function __construct($name = '', $quantity='',$price = '', $tax = '',$type='')
    {
        $this->name = $name;
        $this->price = $price;
        $this->tax = $tax;
        $this->quantity = $quantity;
        $this->type = $type;

    }

    public function __toString()
    {
        $rightCols = 10;
        $leftCols = 12;
        $taxCols = 10;


        $left = str_pad($this->name, $leftCols);
        $quantity = str_pad($this->quantity . "*" . $this->price, $rightCols, ' ', STR_PAD_LEFT);
       
        $tax = '';
        $right='';
        if ($this->type == 'item'){
            $tax = str_pad($this->tax, $taxCols, ' ', STR_PAD_LEFT);
            $right = str_pad((float)$this->price*(float)$this->quantity, $rightCols, ' ', STR_PAD_LEFT);}
        else if ($this->type == 'header'){
            $tax = str_pad($this->tax, $taxCols, ' ', STR_PAD_LEFT);
            $right = str_pad($this->price, $rightCols, ' ', STR_PAD_LEFT);}
        else if($this->type == 'totals') {
            $quantity = str_pad($this->quantity ,$rightCols, ' ', STR_PAD_LEFT);
            return "$left$quantity$right\n";
        }


        return "$left$quantity$right$tax\n";
    }
}

