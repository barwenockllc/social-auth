<?php

namespace Barwenock\SocialAuth\Model\Plugin\ResourceModel\Customer;

class Grid
{
    protected const CUSTOMER_GRID_TABLE = 'customer_grid_flat';

    protected const LOGIN_TYPE_TABLE = 'socialauth_login_type';

    /**
     * @param $interceptor
     * @param $collection
     * @return mixed
     */
    public function afterSearch($interceptor, $collection)
    {
        if ($collection->getMainTable() === self::CUSTOMER_GRID_TABLE) {
            $collection->getSelect()->joinLeft(
                ['slt' => self::LOGIN_TYPE_TABLE],
                'slt.customer_id = main_table.entity_id',
                ['login_type' => 'slt.login_type']
            );

            $wh = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::WHERE);
            foreach ($wh as $key => $condition) {
                if (str_contains($condition, '`main_table`.`socialauth_login_type`')) {
                    $wh[$key] = str_replace(
                        '`main_table`.`socialauth_login_type`',
                        '`slt`.`login_type`',
                        $condition
                    );
                }
            }
            $collection->getSelect()->setPart(\Magento\Framework\DB\Select::WHERE, $wh)->group('main_table.entity_id');
        }
        return $collection;
    }
}
