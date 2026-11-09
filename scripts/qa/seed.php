(new Database\Seeders\RolesSeeder)->run();
App\Support\BusinessProfile::apply("pharmacy"); App\Support\StarterData::seed("pharmacy");
setting()->setMany(["system.installed"=>true,"store.name"=>"MediKart","store.currency_symbol"=>"Rs","store.phone"=>"042 111 222 333","store.email"=>"care@medikart.pk","store.whatsapp"=>"923001234567","store.address"=>"12 Mall Road, Lahore","store.hours"=>"Mon–Sat 9am–10pm","store.locales"=>["en","ur","ur_Latn"]]);
setting()->set("modules.delivery_slots",false); app(App\Services\DemoData::class)->load();
$owner=App\Models\User::create(["name"=>"Owner","email"=>"owner@example.com","password"=>"Demo-pass-123"]); $owner->assignRole("owner");
$u=App\Models\User::create(["name"=>"Sara Khan","email"=>"sara@example.com","password"=>"Demo-pass-123","phone"=>"03001234567"]);
$rate=App\Models\ShippingZone::first()->rates()->where("is_pickup",false)->first();
$p=App\Models\Product::where("name","like","Panadol%")->first();
$rx=App\Models\Product::where("requires_prescription",true)->first();
$cart=app(App\Services\CartService::class);
$o=app(App\Services\CheckoutService::class);
foreach ([[$u,$p,"cod"],[null,$p,"cod"]] as [$usr,$prod,$pm]) {
  $cart->clear(); $cart->add($prod,null,2);
  $ord=$o->place(["name"=>"Sara Khan","email"=>$usr?"sara@example.com":"guest@example.com","phone"=>"03001234567","address_line"=>"House 1, Street 2","city"=>"Lahore","shipping_rate_id"=>$rate->id,"payment_method"=>$pm],$usr);
  echo $ord->trackUrl()."\n";
}
App\Models\ContactMessage::create(["name"=>"Ali","email"=>"ali@example.com","message"=>"Do you deliver at night?"]);
setting()->set("modules.delivery_slots",true); echo "orders:".App\Models\Order::count()."\n";
// ---- extra data for the admin QA
$pharm=App\Models\User::create(["name"=>"Dr Pharma","email"=>"pharm@example.com","password"=>"Demo-pass-123"]); $pharm->assignRole("pharmacist");
$rider=App\Models\User::create(["name"=>"Rider Raza","email"=>"rider@example.com","password"=>"Demo-pass-123"]); $rider->assignRole("rider");
App\Models\Coupon::create(["code"=>"WELCOME10","type"=>"percent","value"=>10,"is_active"=>true]);
App\Models\PrescriptionRequest::create(["name"=>"Ali Raza","phone"=>"03211234567","city"=>"Lahore","notes"=>"Please call after 5pm"]);
$pp=App\Models\Product::where("name","like","Panadol%")->first();
App\Models\Review::create(["product_id"=>$pp->id,"name"=>"Pending Pete","rating"=>3,"title"=>"Okay","body"=>"Waiting for moderation","status"=>"pending"]);
$png=sys_get_temp_dir()."/rx_seed.png"; file_put_contents($png, base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==")); $cart->clear(); $cart->add(App\Models\Product::where("requires_prescription",true)->first(),null,1);
setting()->set("modules.delivery_slots",false);
$rxo=$o->place(["name"=>"Rx Buyer","email"=>"rx@example.com","phone"=>"03007654321","address_line"=>"Flat 9","city"=>"Karachi","shipping_rate_id"=>$rate->id,"payment_method"=>"cod"],null,[new Illuminate\Http\UploadedFile($png,"rx.png","image/png",null,true)]);
setting()->set("modules.delivery_slots",true);
echo json_encode(["rx_order"=>$rxo->id,"order"=>App\Models\Order::first()->id,"product"=>$pp->id,"category"=>App\Models\Category::first()->id,"brand"=>App\Models\Brand::first()->id,"coupon"=>App\Models\Coupon::first()->id,"customer"=>App\Models\User::where("email","sara@example.com")->first()->id,"shipping"=>App\Models\ShippingZone::first()->id,"slot"=>App\Models\DeliverySlot::first()->id,"section"=>App\Models\HomeSection::first()->id,"menu"=>App\Models\MenuItem::first()->id,"page"=>App\Models\Page::first()->id,"article"=>App\Models\Article::first()->id,"review"=>App\Models\Review::first()->id,"role"=>Spatie\Permission\Models\Role::where("name","manager")->first()->id,"staff"=>$pharm->id,"attr"=>App\Models\CatalogAttribute::first()->id])."\n";
