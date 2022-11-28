<?php

namespace App\Imports;

use App\Categories;
use App\Items;
use App\Restorant;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemsImport implements ToModel, WithHeadingRow
{
    public function __construct(Restorant $restorant)
    {
        $this->restorant = $restorant;
        $this->lastCategoryName="";
        $this->lastCategoryID=0;
        $this->lastsubcategoryName="";
        $this->lastsubcategoryID=0;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $category = Categories::where(['name' => $row['maincategory'], 'restorant_id' => $this->restorant->id,"parent_id"=>0])->first();
        $CATID=null;
        if($category != null){
            $CATID= $category->id;
        }else{
            //Check last inssert category
            if($this->lastCategoryName==$row['maincategory']){
                $CATID=$this->lastCategoryID;
            }
        }
        if ($CATID == null) {
            $CATID=DB::table('categories')->insertGetId([
                'name'=>$row['maincategory'],
                'restorant_id'=>$this->restorant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->lastCategoryID=$CATID;
            $this->lastCategoryName=$row['maincategory'];
        }
        $SCATID=null;
        if($row['subcategory'] !=""){
            $subcategory = Categories::where(['name' => $row['subcategory'], 'restorant_id' => $this->restorant->id,"parent_id"=>$CATID])->first();
            
            if($subcategory != null){
                $SCATID= $subcategory->id;
            }else{
                //Check last inssert category
                if($this->lastsubcategoryName==$row['subcategory']){
                    $SCATID=$this->lastsubcategoryID;
                }
            }
            if ($SCATID == null) {
                $SCATID=DB::table('categories')->insertGetId([
                    'name'=>$row['subcategory'],
                    'restorant_id'=>$this->restorant->id,
                    'parent_id'=>$CATID,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->lastsubcategoryName=$SCATID;
                $this->lastsubcategoryID=$row['subcategory'];
            }
        }
        $item_category_id = ($row['subcategory'] !="")?$SCATID:$CATID;
        $item = Items::where(['name' => $row['name'], 'category_id' => $item_category_id])->first();
        
        if($item == null){       
            return new Items([
                'name' => $row['name'],
                'description' => $row['description']?$row['description']:"",
                'price' =>0,
                'category_id' => $item_category_id,
              //  'image' => $row['image'] ? $row['image'] : "",
            ]); 
        }
        
        
    }
}
