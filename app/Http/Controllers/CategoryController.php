<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderby('order', 'ASC')->get();

        return view('admin.category.index', compact('categories'));
    }

    public function pullCategories()
    {
        $categories = app("App\Http\Controllers\Providers\KingsVtuController")->getCategories();
        if (isset($categories['status']) && $categories['status'] == 'success') {
            $categories = $categories['data'] ?? [];
            if(!empty($categories)){
                foreach ($categories as $key=>$category) {
                    $categories = Category::pluck('slug')->toArray();
                    if(!in_array($category['slug'], $categories)){
                        Category::create(
                            [
                                "name" => $category['display_name'],
                                "icon" => $category['icon'] ?? null,
                                "display_name" => $category['display_name'],
                                "slug" => $category['slug'],
                                "order" => $key + 1,
                                "description" => $category['description'],
                                "unique_element" => $category['unique_element'] ?? '',
                                "discount_type" => $category['discount_type'] ?? '',
                            ]
                        );
                    }
                    
                }

                return back()->with('message', 'Categories successfully pulled, please proceed to update categories');
            }else{
                return back()->with('error', 'Categories could not be pulled from provider');
            }
        } else {
            return back()->with('error', 'Error while pulling categories'.$categories['errors'] ?? '');
        }
        
        return view('admin.category.index', compact('categories'));
    }


    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            "name" => "required",
            "display_name" => "nullable",
            "slug" => "required",
            "unique_element" => "required",
            "status" => "required",
            "order" => "required",
            "description" => "nullable",
            "seo_description" => "nullable",
            "seo_title" => "nullable",
            "icon" => "nullable",
            "seo_keywords" => "nullable",
            "discount_type" => "nullable",
        ]);

        Category::updateOrCreate([
            "name" => $request->name,
            "icon" => $request->icon,
            "display_name" => $request->display_name,
            "slug" => $request->slug,
            "status" => $request->status,
            "order" => $request->order,
            "description" => $request->description,
            "seo_description" => $request->seo_description,
            "seo_title" => $request->seo_title,
            "seo_keywords" => $request->seo_keywords,
            "unique_element" => $request->unique_element,
            "discount_type" => $request->discount_type,
        ]);

        return redirect(route('category.index'))->with('message', 'Added successfully');
    }

    public function edit(Category $category)
    {
        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->validate($request, [
            "name" => "required",
            "icon" => "required",
            "display_name" => "nullable",
            "slug" => "required",
            "status" => "required",
            "order" => "required",
            "unique_element" => "required",
            "description" => "nullable",
            "seo_description" => "nullable",
            "seo_title" => "nullable",
            "seo_keywords" => "nullable",
            "discount_type" => "nullable",

        ]);

        $category->update([
            "name" => $request->name,
            "icon" => $request->icon,
            "display_name" => $request->display_name,
            "slug" => $request->slug,
            "status" => $request->status,
            "unique_element" => $request->unique_element,
            "order" => $request->order,
            "description" => $request->description,
            "seo_description" => $request->seo_description,
            "seo_title" => $request->seo_title,
            "seo_keywords" => $request->seo_keywords,
            "discount_type" => $request->discount_type,
        ]);

        return back()->with('message', 'Updated successfully');
    }

    public function destroy(Category $category){
        if($category->products->count() < 1){
            $category->delete();
        }else{
            return back()->with('error', 'Category in use, cannot delete');
        }

        return back()->with('message', 'Deleted successfully');

    }
}
