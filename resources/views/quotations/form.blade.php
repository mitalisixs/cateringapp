<div class="row" style="transform: none;">
    <div class="col-lg-8" id="mainContent" style="position: relative; overflow: visible; box-sizing: border-box; min-height: 1px;">
        <!-- Calculator Area -->





        <!-- Calculator Area End -->
        <!-- Personal Details Start -->

        <!-- Personal Details End -->
        <div class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 1px; position: static; transform: none;">
            @foreach($categories as $index=>$category)
            <div id="optionGroup{{$index}}" class="row option-box">
                <!-- <div class="ribbon-left"><span class="left">Popular</span></div> -->
                <div class="option-box-header">
                    <h3>{{$category->name}}</h3>
                    <!-- <p>Subtitle or short description can be set here.</p> -->
                </div>
                <?php
                $subcategories =  $category->subcategories;

                ?>
                @if(count($category->items)>0 && $category->active == 1)

                <div class="col-md-6 col-sm-6">
                    <select id="optionGroup{{$index}}List" class="wide  item_list" name="optionGroup{{$index}}List">
                        <option value="0">Select</option>
                        @foreach ( $category->items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>

                </div>
                @endif
                @if(count($subcategories)>0 && $category->active == 1)

                    @foreach ( $subcategories as $sindex=>$subcategory)
                        @if(count($subcategory->items)>0 && $subcategory->active == 1)
                        <div class="col-md-6 col-sm-6 mb-4"><h4>{{$subcategory->name}}</h4></div>
                        <div class="col-md-6 col-sm-6 mb-4">
                            <select id="optionGroup{{$index}}List_{{$sindex}}" class="wide item_list form-control" name="optionGroup{{$index}}List[{{$index}}][{{$sindex}}]">
                                <option value="0">Select</option>
                                @foreach ( $subcategory->items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>

                        </div>
                        
                        @endif
                    @endforeach

                @endif
            </div>


            @endforeach
        

            <div id="personalDetails">
                <div class="row">
                    <div class="order-box-header">
                        <h3>Order Form</h3>
                        <p>Subtitle or short description can be set here. <a href="javascript:;" id="autofill" class="underline-link">Autofill</a></p>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Name</label>
                            <input id="username" class="form-control" name="username" placeholder="Enter Full Name" type="text" data-parsley-pattern="^[a-zA-Z\s.]+$" required="">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input id="email" class="form-control" name="email" placeholder="Enter VALID EMAIL and check the result" type="email" required="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Phone</label>
                            <input id="phone" class="form-control" name="phone" placeholder="Enter Phone e.g.: +363012345" type="text" data-parsley-pattern="^\+{1}[0-9]+$">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Address</label>
                            <input id="address" class="form-control" name="address" placeholder="Enter Address" type="text" data-parsley-pattern="^[a-zA-Z0-9\s.]+$" required="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>City</label>
                            <input id="city" class="form-control" name="city" placeholder="Enter City" type="text" pattern="^[a-zA-Z\s]+$" required="">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>State</label>
                            <input id="state" class="form-control" name="state" placeholder="Enter State" type="text" data-parsley-pattern="^[a-zA-Z\s]+$" required="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Zip Code</label>
                            <input id="zipcode" class="form-control" name="zipcode" placeholder="Enter Zip Code" type="text" data-parsley-type="digits" required="">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            <label>Country</label>
                            <input id="autocomplete" class="form-control" name="country" placeholder="Start Typing Country" type="text" required="" data-parsley-pattern="^[a-zA-Z\s]+$" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Message</label>
                            <textarea id="inputMessage" class="form-control" name="message" placeholder="Enter Message" data-parsley-pattern="^[a-zA-Z0-9\s.:,!?&#39;]+$"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="resize-sensor" style="position: absolute; inset: 0px; overflow: hidden; z-index: -1; visibility: hidden;">
                <div class="resize-sensor-expand" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: hidden; z-index: -1; visibility: hidden;">
                    <div style="position: absolute; left: 0px; top: 0px; transition: all 0s ease 0s; width: 770px; height: 1905px;">
                    </div>
                </div>
                <div class="resize-sensor-shrink" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: hidden; z-index: -1; visibility: hidden;">
                    <div style="position: absolute; left: 0; top: 0; transition: 0s; width: 200%; height: 200%">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4" id="sidebar" style="position: relative; overflow: visible; box-sizing: border-box; min-height: 1px;">
        <!-- Order Container -->
        <div id="orderContainer" class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 0px; position: static; transform: none;">
            <div class="row">
                <div class="col-md-12">
                   
                    <h3>Quotation Summary</h3>
                    <ul id="orderSumList">
                       
                    </ul>
                    <!-- <div class="row total-container">
                        <div class="col-6 p-0">
                            <input type="text" id="totalTitle" class="summaryInput" name="totallabel" value="" disabled="">
                        </div>
                        <div class="col-6 p-0">
                            <input type="text" id="total" class="summaryInput" name="total" value="0" data-parsley-errors-container="#totalError" data-parsley-empty-order="" disabled="">
                        </div>
                    </div> -->
                    <div id="totalError"></div>
                </div>
            </div>
            
            <div class="row">
                <div class="text-center">
                    <button type="submit" class="btn btn-success mt-4">Save</button>
                </div>
            </div>
            
        </div>
        <!-- Order Container End -->
    </div>
</div>