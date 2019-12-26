# Magento2-Plugin-Vbout
Magento 2 Plugin that link Metadata of orders, carts customers , searches, products and with Integration settings.

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
  
 Variations in Magento are handeled as a product with aditional SKU (ammended to the original SKu EX: orginialsky-newsku : xxxx-yyyy). 
 For this they are being handeled as Parent product (origninal SKU and Price ) are added to product Feed, and all the ammendments ( SKKU and Price ) 
 are added in the cart product data.
 
 Variations are sent as an array upon adding a product, syncing a product and viewing a produnct.
 
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
          there is a listener for both Cart Update and Cart create and they have the following functionalities : 
            - Get the current logged in customer and it's data. 
            - Create a new cart
            - Products are added ( a loop to handle them ) 
  ### Cart Item Remove : 
          There is a listener for cart remove item, nonetheless, it doesnt send the item ID , and the data that it presents was protected and we didn't have the access. 
          This was solved by creating a new API function to EmptyCart, where we we emptied all cart items, and then re-added them. Since the observer returns the Items left in cart.
  ### Orders Create and Update : 
      The both have different listeners, they work the same. An Order is added with Shipping and Biling information, alongside with customer's information.
      
      - Updating Cart : 
          - In the process of updating cart, any update to status( Cancelled, Pending, Paid, Shipped/success), details, products is updated directly.
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
                    *customer_register_success*           | `CreateCustomer`            | `This function is to create users on registrations.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
        *__checkout_type_onepage_save_order_after__*      | `CreateOrder`               | `This function for checkout with a cart with prior log in.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *checkout_cart_add_product_complete*  | `AddToCart`                 | `This function to Add To Cart Function, that creates a cart and Adds the Items`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *sales_quote_remove_item*             | `CartRemoveItem`            | `Removes cart items from a cart.`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *catalog_controller_product_view*     | `ProductView`               | `This function to send the product view by the customer or IP address ( then it can be linked)`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------
                    *checkout_submit_all_after*           | `CreateOrder`               | `Create an order with all it's data (After placing an order).`
                    ------------------------------------  | --------------------------- | ------------------------------------------------------------------


        - In Adminstration Panel. Must be added in under <adminhtml> <events> section: 
        
            Listener                              | Function Name               | Descritpion
       -----------------------------------------  | --------------------------- | ------------------------------------------------------------------
            *catalog_product_save_after*          | `AddProduct`                | `Once a product is added or Updated.`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
      *admin_system_config_changed_section_vbout* | `AfterSaveConfig`           | `Customized configuration for integration settings between Magento and Vbout. This also allows the user to control what features they want in the 
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
            *sales_order_save_after*              | `UpdateOrder`               | `When an admin wants to Update order, it updates the order and order status(taking in consideration Billing and Shipping info).`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
        *abstract_search_result_load_after*       | `ProductSearch`             | `This is a listener on every page load, it checks if the page has search query to be able to get searched queries.`
            ------------------------------------  | --------------------------- | ------------------------------------------------------------------
