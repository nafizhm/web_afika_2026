<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Property extends Model {
protected $fillable=['name','location','price','land_area','building_area','bedrooms','bathrooms','description','image','is_published'];
protected function casts(): array { return ['is_published'=>'boolean']; }
public function imageUrl(): string { return $this->image ? asset('storage/'.$this->image) : asset('images/residence.svg'); }
}
