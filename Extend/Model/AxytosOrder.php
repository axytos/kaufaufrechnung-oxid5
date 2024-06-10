<?php

class AxytosOrder extends AxytosOrder_parent
{
    /**
     * @return void
     */
    public function initializeOrderNumber()
    {
        // _setNumber will be renamed to setNumber in future releases of oxid6
        // also setNumber and _setNumber may be private/protected, but we need to initialize an order number
        if (method_exists($this, 'setNumber')) {
            // OXID 7 and higher
            $this->setNumber();
        } else {
            // OXID 6 and below
            $this->_setNumber();
        }
    }
}
