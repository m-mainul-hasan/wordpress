<?php
class VendorSyncManager
{

    private $inventoryType;
    private $dbHandle;
    private $wcProductFactory;

    public function __construct()
    {
    }

    public function setInventoryType($inventoryType)
    {
        $this->inventoryType = $inventoryType;
    }

    public function setDbHandle($dbHandle)
    {
        $this->dbHandle = $dbHandle;
    }

    public function setWcProductFactory($wcProductFactory)
    {
        $this->wcProductFactory = $wcProductFactory;
    }

    public function getProductTitleWithoutCode($productTitleWithCode)
    {
        $titleParts = explode('-', $productTitleWithCode);
        if (count($titleParts) > 1 && strlen($titleParts[1]) > 0) {
            $title = trim($titleParts[1]);
        } else {
            $title = trim($titleParts[0]);
        }

        return $title;
    }

    public function sync()
    {
        switch ($this->inventoryType) {

            case 'managed':
                global $vendor_db_link;
                $sku = 'sku';
                $difference_date = date( 'Y-m-d', strtotime('-5 days -10 hours'));
                $vendor_products_sql = "SELECT * FROM products ";
                $vendor_products_sql .= "WHERE `sku` IS NOT NULL ";
                $vendor_products_sql .= " AND date(`LastQtyUpdate`) = '{$difference_date}'";
                $vendor_products_sql .= " AND `sku` NOT LIKE '5%'";
                $vendor_products_sql .= " AND `StoreId` = 2";

                if ($result = $vendor_db_link -> query($vendor_products_sql)) {
                    while ($product = $result -> fetch_object()) {
                        echo current_time('Y-m-d H:i:s') . " Sync of {$product->$sku} started." . PHP_EOL;

                        $wcProductId = $this->dbHandle->get_var("SELECT post_id FROM r4_postmeta WHERE meta_key = '_sku' AND meta_value = '{$product->$sku}' LIMIT 1");

                        if ($wcProductId) {

                            $wcProduct = wc_get_product($wcProductId);

                            if ($wcProduct) {
                                $wcProduct->update_meta_data('vendor_inventory_type', 'managed');
                                $wcProduct->update_meta_data('vendor_cost', $product->Price);
                                $wcProduct->update_meta_data('scientific_name', $product->ScientificName);

                                $wcProduct->set_manage_stock(true);
                                $wcProduct->set_stock_quantity($product->QtyAvail);
//                                $wcProduct->set_price(floatval($product->Price) * 2);
//                                $wcProduct->set_regular_price(floatval($product->Price) * 2);

                                if ($product->QtyAvail <= 0) {
                                    $visibility = 'hidden';
                                } else {
                                    $visibility = 'visible';
                                }

                                $wcProduct->set_catalog_visibility($visibility);
                                $wcProduct->save();

                                if ($wcProduct->get_type() == 'variation') {
                                    $parentProductId = $wcProduct->get_parent_id();
                                    $parentProduct = wc_get_product($parentProductId);
                                    $parentProduct->set_manage_stock(false);
                                    $parentProduct->update_meta_data('vendor_inventory_type', 'managed');
                                    $parentProduct->save();
                                }
                            }
                        }

                        echo current_time('Y-m-d H:i:s') . " Sync of {$product->$sku} ended." . PHP_EOL;
                    }
                    $result -> free_result();
                }

                echo current_time('Y-m-d H:i:s') . " Sync completed for MANAGED products." . PHP_EOL;

                break;

            case 'live':

                global $vendor_db_link;
                $sku = 'sku';
                $vendor_live_products_sql = "SELECT * FROM products ";
                $vendor_live_products_sql .= "WHERE `sku` IS NOT NULL ";
                $vendor_live_products_sql .= " AND `sku` LIKE '5%'";
                $vendor_live_products_sql .= " AND `StoreId` = 2";

                if ($result = $vendor_db_link -> query($vendor_live_products_sql)) {
                    while ($product = $result->fetch_object()) {
                        echo current_time('Y-m-d H:i:s') . " Sync of {$product->$sku} started." . PHP_EOL;

                        $wcProductId = $this->dbHandle->get_var("SELECT post_id FROM r4_postmeta WHERE meta_key = '_sku' AND meta_value = '{$product->$sku}' LIMIT 1");
                        if ($wcProductId) {
                            print "Existing eibi product found." . PHP_EOL;
                            // Existing product
                            $wcProduct = wc_get_product($wcProductId);
                            if ($wcProduct) {
                                $wcProduct->set_name($this->getProductTitleWithoutCode($product->Description));
                                $wcProduct->set_short_description($product->Description);
                                $wcProduct->set_description($product->Description);
                                $wcProduct->set_slug("wysiwyg-{$product->Description}");

                                $wcProduct->update_meta_data('vendor_inventory_type', 'live');
                                $wcProduct->update_meta_data('vendor_cost', $product->Price);

                                $wcProduct->set_manage_stock(true);
                                $wcProduct->set_stock_quantity($product->QtyAvail);
                                $wcProduct->set_price(floatval($product->Price) * 2);
                                $wcProduct->set_regular_price(floatval($product->Price) * 2);

                                if (intval($product->Price) <= 0 || $product->QtyAvail <= 0) {
                                    $visibility = 'hidden';
                                } else {
                                    $visibility = 'visible';
                                }

                                $wcProduct->set_catalog_visibility($visibility);

                                print "Eibi product visibility set to: {$visibility}" . PHP_EOL;

                                $wcProduct->save();

                            }

                        } else {
                            // New product
                            $wcProduct = new WC_Product();
                            $wcProduct->set_name($this->getProductTitleWithoutCode($product->Description));
                            $wcProduct->set_sku($product->$sku);
                            $wcProduct->set_short_description($product->Description);
                            $wcProduct->set_description($product->Description);
                            $wcProduct->set_slug("wysiwyg-{$product->Description}");
                            $wcProduct->update_meta_data('vendor_inventory_type', 'live');
                            $wcProduct->update_meta_data('vendor_cost', $product->Price);
                            $wcProduct->set_manage_stock(true);
                            $wcProduct->set_stock_quantity($product->QtyAvail);
                            $wcProduct->set_price(floatval($product->Price) * 2);
                            $wcProduct->set_regular_price(floatval($product->Price) * 2);

                            if (intval($product->Price) == 0 || $product->QtyAvail <= 0) {
                                $visibility = 'hidden';
                            } else {
                                $visibility = 'visible';
                            }

                            $wcProduct->set_catalog_visibility($visibility);

                            print "Eibi product visibility set to: {$visibility}" . PHP_EOL;

                            $wcProduct->set_category_ids(array(23)); // Cat ID of WYSIWYG = 23

                            $wcProduct->save();

                        }

                        echo current_time('Y-m-d H:i:s') . " Sync of {$product->$sku} ended." . PHP_EOL;
                    }
                    $result -> free_result();
                }

                break;
        }
    }
}


class VendorSyncManagerFactory
{
    private static $allowedInventoryTypes = array('managed', 'live');

    public static function get($inventoryType)
    {
        if (in_array($inventoryType, self::$allowedInventoryTypes)) {
            $syncManager = new VendorSyncManager();
            $syncManager->setInventoryType($inventoryType);
            return $syncManager;
        } else {
            throw new Exception("SyncManager cannot be initialized with given type.");
        }
    }
}
