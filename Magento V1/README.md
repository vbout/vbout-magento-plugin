# Magento-Plugin-Vbout
Magento Plugin that link Metadata of orders, carts customers , searches, products and with Integration settings.

## The Plugin has the Following Features :

  - Abandoned Cart Data
  - Search Data 
  - Registering a new customer data
  - Adding a new Product Data ( With variations, Category , price and images , descriptions)
  - Product Visits Data
  - Product Category Visit Data
  - Syncing Customers ( For customer data prior the use of the plugin) 
  - Syncing Product   ( For Product data prior the use of the plugin)
## limitations : 
    1 - You can't remove / uninstall the plugin, since it is installed manually, and uninstalling the file might cauase a problem, since we need to pluck the files from Magento.
    2 - The functions with Magento 2 might be depriciated.
    3 - if the Administrator has many Customers/Products, the sync might take a while , so it needs optimization with cron.php in Magento.
  
## Variations : 
  
Variations are sent as an array upon adding a product, syncing a product and viewing a product. But when purchasing a certain variation of a product, we send the new product data to be viewed ( New Category, New Price, New Variation Name , New SKU) to be previewed in Vbout.
 
## Search : 
  
  There is a no observer for hooks activity for this we added a listener/observer for every page load, where we searched if it has Getter 'q'
  if it is present, this means than there is a search query and we handle it.
  
## Orders and Abandonded Carts : 
  
  ### Checkout : 
        There is a listener for checkout and does the following :
          - A new customer is created ( since there is a no prior log in)
          - Cart is created
          - Products are added ( a loop to handle them )
  ### Create and Update Cart  : 
        There is a listener for both Cart Update and Cart create and they have the following functionalities : 
          - Get the current logged in customer and it's data. 
          - Create a new cart
          - Products are added ( a loop to handle them ) 
          - Updating Cart : 
              - In the process of updating cart, any update to status( Cancelled, Pending, Paid, Shipped/success), details, products is updated directly.

  ### Cart Item Remove : 
          There is a listener for cart remove item, nonetheless, it doesnt send the item ID , and the data that it presents was protected and we didn't have the access. 
          This was solved by creating a new API function to EmptyCart, where we we emptied all cart items, and then re-added them. Since the observer returns the Items left in cart.
  ### Orders Create and Update : 
      The both have different listeners, they work the same. An Order is added with Shipping and Biling information, alongside with customer's information.
      
## Customers Add, Update and Sync :
    - Customers are added, updated on registering and checkout.
    - Customer's sync adds all the users in the system that had previous orders or registered.

## Product Add Update and Sync :
    - Products are added , updated on an Admin page.
    - Product's sync adds all products that are in the system that were added and are still in stock.
    
## IP and Customer Link: 
    - If a customer is roaming the website, and the features are turned on, the IP will be sent at every event. 
    - Upon User registration, all the searches, Cart , product and category views will be linked to this account through IP.
    
      
## Listeners ---- > Functions Used and Explanation :

      - In frontend Section Must be added under <frontend> <events> section
  
                    Listener                              | Function Name               | Descritpion
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *__customer_register_successk__*      | `customerRegisterSuccess`   | `This function is to create users on registrations.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
        *__checkout_type_onepage_save_order_after__*      | `checkoutCartOrderCreate`   | `This function for checkout with a cart with prior log in.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *__checkout_cart_save_after__*        | `cartCheckoutCreate`        | `This function to Add To Cart Function, that creates a cart and Adds the Items`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *__sales_quote_item_delete_after__*   | `cartRemoveItem`            | `Removes cart items from a cart.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *__catalog_controller_product_view__* | `productView`               | `This function to send the product view by the customer or IP address ( then it can be linked)`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *__checkout_submit_all_after__*       | `salesOrderPlaceAfter`      | `Create an order with all it's data (After placing an order).`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------

        - In Adminstration Panel. Must be added in under <adminhtml> <events> section: 
        
            Listener                              | Function Name               | Descritpion
       -----------------------------------------  | --------------------------- | ------------------------------------------------------------------
            *__catalog_product_save_after__*      | `catalogProductSaveAfter`   | `Once a product is added or Updated.`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
            *__adminhtml_init_system_config__*    | `customSystemConfig`        | `Customized configuration for integration settings between Magento and Vbout. This also allows the user to control what features they want in the admin/Configuration/Vbout page.`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
            *__sales_order_save_commit_after__*   | `adminUpdateOrder`          | `When an admin wants to Update order, it updates the order and order status(taking in consideration Billing and Shipping info).`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
        *__controller_action_layout_load_before__*| `productSearchQuery`        | `This is a listener on every page load, it checks if the page has search query to be able to get searched queries.`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
