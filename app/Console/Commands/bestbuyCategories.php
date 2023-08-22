<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\V1\CategoriesController;
use App\Models\Category;
use Illuminate\Http\Request;
ini_set('memory_limit', '-1');

class bestbuyCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:Categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and store categories from bestbuy';

    protected $key = "fjsprJXl4QzVRV7f5MolEhhD"; 
    protected $url = "https://api.bestbuy.com/v1/categories(id=abcat*)";

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
        $this->newMethodForCategories();
        exit;

        $response = $this->getCategories(1);    
        if ($response->getStatusCode() != 200)
            exit("Error in response");

        $body = json_decode($response->getBody());
        $totalPages = $body->totalPages;
        
        $progressBar = $this->output->createProgressBar($body->total);
        $this->info("Preparing Categories Batch"."\n");
        $progressBar->start();

        for ($page=1; $page <= 4 ; $page++) 
        { 

            $response = $this->getCategories($page, "id,name,active,subCategories");
            if ($response->getStatusCode() == 200)
            {
                $data = json_decode($response->getBody());
                foreach($data->categories as $category)
                {
                    if($category->active == true)
                    {
                        $check_products = $this->getProducts($category->id);
                        if ($check_products == true)
                        {
                            if ($this->isCategoryExist($category->id) == false)
                                $store = Category::create([
                                "name"  => $category->name,
                                "unique_id" =>  $category->id,
                                "cat_type"  =>  "bestbuy",
                                "parent_id" =>  0,
                                "status" => "active",
                                "created_by" => "1",
                                "created_ip" => request()->ip()]);  
                        }   
                        $progressBar->advance();
                    }
                }   
            }
        }
        
        $progressBar->finish();        
        $this->info("\n prepared!");

    }

    private function isCategoryExist($categoryid){
        if(isset($categoryid) && !empty($categoryid)){
            $checkProduct = Category::Select("id")
                                    ->where("unique_id",$categoryid)
                                    ->where("parent_id", 0)
                                    ->get();

            if($checkProduct->count() > 0)
                return true;
            
            return false;
        }
    }

    private function getCategories($page=1, $keys = "id"){
        $params = ['query' => [
            "format" => "json",
            "show" => $keys,
            "page" => $page,
            "pageSize" => "100",
            'apiKey' => $this->key,]];
        $response = apiRequest($this->url, "GET", $params);
        return $response;
    }

    private function getProducts($category){
        $url = "https://api.bestbuy.com/v1/products(categoryPath.id=".$category."&onlineAvailability=true&inStoreAvailability=true)";
        $params = ['query' => [
            "format" => "json",
            "show" => "sku",
            "pageSize" => "100",
            'apiKey' => $this->key,
        ]];
        $response = apiRequest($url, "GET", $params);
        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody());
            if (empty($body->products)) {
                return false;
            }
            return true;
        }
    }

    private function getSelectiveCategories(){
        $categories = [
                        "pcmcat209400050001",
                        "abcat0501000",
                        "abcat0401000",
                        "pcmcat242800050021",
                        "abcat0204000",
                        "pcmcat241600050001",
                        "pcmcat254000050002",
                        "pcmcat209000050006",
                        "abcat0502000",
                        "pcmcat232900050000",
                        "pcmcat295700050012",
                        "pcmcat310200050004",
                        "pcmcat243400050029",
                        "abcat0904000",
                        "abcat0901000",
                        "abcat0912000",
                        "abcat0101000",
                        "abcat0910000",
                        "pcmcat273800050036",
                        "pcmcat300300050002"
                    ];
        return $categories;
    }

    private function newMethodForCategories(){
        
        $cat_ids = $this->getSelectiveCategories();
        foreach ($cat_ids as $key => $id) {
            
            $url_selective = "https://api.bestbuy.com/v1/categories(id=$id)";
            $params = ['query' => [
                "format" => "json",
                "pageSize" => "100",
                'apiKey' => $this->key,]];

            $response = apiRequest($url_selective, "GET", $params);
            
            if ($response->getStatusCode() != 200){
                exit("Error in response");
            }
            
            $body = json_decode($response->getBody());
            foreach ($body->categories as $category) {
                if ($category->active == true) {
                    if ($this->isCategoryExist($category->id) == false) {
                        $store = Category::create([
                            "name"  => $category->name,
                            "unique_id" =>  $category->id,
                            "cat_type"  =>  "bestbuy",
                            "parent_id" =>  0,
                            "status" => "active",
                            "created_by" => "1",
                            "created_ip" => request()->ip()]); 
                        
                        if($store){
                            if(!is_null($category->subCategories)){
                                foreach ($category->subCategories as $subcategory) {
                                    Category::create([
                                        "name"  => $subcategory->name,
                                        "unique_id" =>  $subcategory->id,
                                        "cat_type"  =>  "bestbuy",
                                        "parent_id" =>  $store->id,
                                        "status" => "active",
                                        "created_by" => "1",
                                        "created_ip" => request()->ip()
                                        ]); 
                                }
                            }
                        }
                    }
                }
            }
        }

    }
}
