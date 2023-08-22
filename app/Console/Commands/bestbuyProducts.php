<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\V1\CategoriesController;
use App\Http\Controllers\V1\Products\BestBuyController;
use App\Models\Products\BestBuy;
use App\Models\Products\ProductAttribute;
use App\Models\Category;
use App\Models\CronLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

ini_set('memory_limit', '-1');

class bestbuyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and store Products from bestbuy';
    protected $chunk = 1;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Request $request)
    {
        $cronLog = CronLog::where("name" , "BestBuy Products")
        ->whereDate('updated_at', Carbon::today())
        ->latest("id")
        ->first();
         
        if (!is_null($cronLog)) {
            parse_str($cronLog->last_runtime_value, $output);
            $cron_status = $cronLog->status;
            $cronId = $cronLog->id;
        
        }else{
            
            $prevCronLog = CronLog::where("name" , "BestBuy Products")
            ->whereDate('updated_at', Carbon::yesterday())
            ->latest("id")
            ->first();
            
            if (!is_null($prevCronLog) && ($prevCronLog->count() <= Category::count())){
                parse_str($prevCronLog->last_runtime_value, $output);
                $cron_status = $prevCronLog->status;
                $cronId = $prevCronLog->id;
            }else{
                
                $cronLog = CronLog::Log([
                                    "name"  =>  "BestBuy Products", 
                                    "message" => "lastcat=1&chunk=".$this->chunk, 
                                    "last_runtime_value" => "lastcat=1", 
                                    "status" => 'inprogress'
                                ]);
                $cron_status = "new";
                $cronId = $cronLog->id;
            }
        }

        if ($cron_status == "complete") 
        {
            $start = $output['lastcat'];
            $startPage = 1;
        }
        else if ($cron_status == "inprogress")
        {    
            $start = $output['lastcat']-1;
            
            if(isset($output["pageStatus"]) && isset($output["page"])){
                if($output["pageStatus"] == "Completed")
                    $startPage =  $output['page'] + 1;
                else
                    $startPage =  $output['page'];    
            }else{
                $startPage = 1;
            }
        }
        else if ($cron_status == "new")
        {
            $start = 0;
            $startPage = 1;
        }

        if($start >= Category::count())
            $start = 0;
        
        $getCategories = $this->getCategories($start);
        $progressBar = $this->output->createProgressBar(count($getCategories));
        $progressBar->start();

        foreach($getCategories as $category)
        {
            $Log = CronLog::Log([
                                "name"  =>  "BestBuy Products", 
                                "message" => "uniqueCatId=$category->unique_id&lastcat=".$category->id."&chunk=".$this->chunk, 
                                "last_runtime_value" => "lastcat=$category->id", 
                                "status" => 'inprogress'
                            ]);

            $response = $this->getProducts($category->unique_id);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody());
                if (!empty($response->products)) {
                    $totalPages = $response->totalPages;
                    
                    $this->info("\n Products against category $category->unique_id is Preparing!"."\n");
                    $progressBar2 = $this->output->createProgressBar($totalPages);
                    $progressBar2->start();

                    for ($page=$startPage; $page <= $totalPages ; $page++) {

                        $response = $this->getProducts($category->unique_id, $page, "all");
                        if ($response->getStatusCode() == 200) {
                            $response = json_decode($response->getBody());
                            if (!empty($response->products)) {
                                
                                CronLog::where("id", $Log->id)->update(["last_runtime_value" => "uniqueCatId=$category->unique_id&lastcat=$category->id&totalPages=$totalPages&page=$page&pageStatus=Inprogress"]);
                                foreach ($response->products as $product) {
                                    if ($product->active == true) {
                                        if($product->quantityLimit > 0 && $product->quantityLimit != null){
                                            if ($this->isProductExist($product->sku) == false) {
                                                $this->prepareProducts($category, $product);
                
                                                CronLog::where("id", $Log->id)->update(["last_runtime_value" => "cat=$category->id&totalPages=$totalPages&page=$page&pageStatus=Inprogress&lastaddedSku=$product->sku"]);
                                            }
                                        }
                                    }
                                }
                                CronLog::where("id", $Log->id)->update(["last_runtime_value" => "uniqueCatId=$category->unique_id&lastcat=$category->id&totalPages=$totalPages&page=$page&pageStatus=Completed"]);
                            }
                        }else{
                            $this->sendNotification($response);
                        }

                        $progressBar2->advance();
                    }
        
                    $this->info("\n Products Prepared!"."\n")   ;
                    $progressBar2->finish();
                }
                CronLog::where("id", $Log->id)->update(["last_runtime_value" => "lastcat=$category->id&totalPages=$totalPages&pageStatus=Done","status" => "complete"]);
                $progressBar->advance();
            }
        }
        $progressBar->finish();
        $this->info("\n Successfully Inserted!");
    }

    private function isProductExist($productSku, $bool = false){
        if(isset($productSku) && !empty($productSku)){
            $checkProduct = Bestbuy::Select("id")
                                    ->where("product_number",$productSku)
                                    ->get();

            if($checkProduct->count() > 0)
                $bool =  true;
            
        }
        return $bool;
    }

    private function prepareProducts($category, $product){
        $insert_arr = [
            "cat_id" => $category->parent_id,
            "subcat_id" => $category->id,
            "model_number" => $product->modelNumber,
            "product_number" => $product->sku,
            "name" => $product->name,
            "brand" => $product->manufacturer,
            "points" => $product->salePrice,
            "description" => $product->longDescription,
            "image" => $product->image,
            "rating" => $product->customerReviewAverage,
            "inventory" => $product->quantityLimit,
            "status" => "active",
            "created_by" => "1",
            "created_ip" => "182.180.172.119",
        ];

        $response = BestBuy::create($insert_arr);
        $this->storeProductAttributes($product, $response->id);
    }

    private function storeProductAttributes($product, $productid){
        
        $attributes = array();

        if (isset($product->upc) && !is_null(isset($product->upc))) 
            $attributes = $this->prepareAttrubute($attributes, "upc", $product->upc, $productid);

        if (isset($product->regularPrice) && !is_null(isset($product->regularPrice)))
            $attributes = $this->prepareAttrubute($attributes, "regularPrice", $product->regularPrice, $productid);

        if (isset($product->salePrice) && !is_null(isset($product->salePrice)))
            $attributes = $this->prepareAttrubute($attributes, "salePrice", $product->salePrice, $productid);

        if (isset($product->onSale) && !is_null(isset($product->onSale)))
            $attributes = $this->prepareAttrubute($attributes, "onSale", $product->onSale, $productid);

        if (isset($product->customerReviewCount) && !is_null(isset($product->customerReviewCount)))
            $attributes = $this->prepareAttrubute($attributes, "customerReviewCount", $product->customerReviewCount, $productid);

        if (isset($product->customerReviewAverage) && !is_null(isset($product->customerReviewAverage)))
            $attributes = $this->prepareAttrubute($attributes, "customerReviewAverage", $product->customerReviewAverage, $productid);    

        if (isset($product->customerTopRated) && !is_null(isset($product->customerTopRated)))
            $attributes = $this->prepareAttrubute($attributes, "customerTopRated", $product->customerTopRated, $productid);                
        
        if (isset($product->bestSellingRank) && !is_null(isset($product->bestSellingRank)))
            $attributes = $this->prepareAttrubute($attributes, "bestSellingRank", $product->bestSellingRank, $productid);
        
        if (isset($product->images) && !is_null(isset($product->images)))
            $attributes = $this->prepareAttrubute($attributes, "images", json_encode($product->images), $productid);
            
        if (isset($product->details) && !is_null($product->details)) {
            foreach ($product->details as $productdetail) {
                $attributes = $this->prepareAttrubute($attributes, $productdetail->name, $productdetail->value, $productid);
            }
        }

        if(isset($attributes) && !empty($attributes))
            $productattributes = ProductAttribute::insert($attributes);
    }

    private function prepareAttrubute($attribute, $attr, $value, $productid){
        $attribute[] = ["product_id" => $productid, 
         "product_type" => "bestbuy",
         "attribute" => $attr,
         "value" => $value,
         "created_by" => "1",
         "created_ip" => get_client_ip()
        ];

        return $attribute;
    }

    private function getCategories($lastcat){
        $getCategories = Category::select(["id","name","unique_id","parent_id"])
                                    ->where("cat_type","bestbuy")
                                    ->where("parent_id","!=",0)
                                    ->skip($lastcat)
                                    ->take($this->chunk)
                                    ->get();
        return $getCategories;
    }
    
    private function getProducts($bestbuy_cat, $page = 1, $keys = "sku"){
        $url = "https://api.bestbuy.com/v1/products(categoryPath.id=".$bestbuy_cat."&onlineAvailability=true&inStoreAvailability=true)";
        $params = ['query' => [
            "format" => "json",
            "show" => $keys,
            "pageSize" => "100",
            "page" => $page,
            'apiKey' => "fjsprJXl4QzVRV7f5MolEhhD",
        ]];
        $response = apiRequest($url, "GET", $params);
        return $response;
    }

    // that was for R&D
    private function getProductsVariations(){
        
        $productVariation = array();
        for ($i=1; $i < 4; $i++) { 
            $url = "https://api.bestbuy.com/v1/products(categoryPath.id=pcmcat300300050002&onlineAvailability=true&inStoreAvailability=true)";
            $params = ['query' => [
                "format" => "json",
                "show" => "all",
                "pageSize" => "100",
                "page" => $i,
                'apiKey' => "fjsprJXl4QzVRV7f5MolEhhD",
            ]];
            $response = apiRequest($url, "GET", $params);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody());
                if (!empty($response->products)) {
                    foreach ($response->products as $product) {
                        if ($product->active == true) {
                            if(!empty($product->productVariations)){ 
                                foreach($product->productVariations as $productVariations){
                                    if (!empty($productVariations)) 
                                    {
                                        $variation = $productVariations->variations;
                                        if (!empty($variation)) {
                                            $variation = explode(':', $variation[0]->name);
                                            $productVariation[] = $variation[1];
                                        }
                                    }
                                }
                            }
                        }
                    }
                } 
            }
        }
        print_r(array_unique($productVariation));
    }

    private function sendNotification($response){
        
        $data = array("name"=> "BestBuy Products Script", "response" => $response);
        Mail::send('mails.template1', $data, function($message) {

            $message->to("talha@thesparksolutionz.com", "Talha Hanif")
                    ->subject("Response from bestbuy Script");
        
        });
    
    }
}