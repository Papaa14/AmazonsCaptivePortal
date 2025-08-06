@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Expense Create')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('expense.index')}}">{{__('Expense')}}</a></li>
    <li class="breadcrumb-item">{{__('Expense Create')}}</li>
@endsection
@push('script-page')
    <script src="{{asset('js/jquery-ui.min.js')}}"></script>
    <script src="{{asset('js/jquery.repeater.min.js')}}"></script>
    <script src="{{ asset('js/jquery-searchbox.js') }}"></script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function () {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }

                    // for item SearchBox ( this function is  custom Js )
                    JsSearchBox();

                    // $('.select2').select2();
                },
                hide: function (deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        $(".price").change();
                        $(".discount").change();
                        $('.item option').prop('hidden', false);
                        $('.item :selected').each(function () {
                            var id = $(this).val();
                            if (id) {
                                $('.item').not(this).find("option[value=" + id + "]").prop('hidden', true);
                            }
                        });

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
                },
                ready: function (setIndexes) {
                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
            }

        }

        $(document).on('change', '.item', function () {

            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);


            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function (data) {
                    var item = JSON.parse(data);
                    // console.log(item)

                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.purchase_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product.description);

                    var taxes = '';
                    var tax = [];

                    var totalItemTaxRate = 0;
                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name + ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (item.product.purchase_price * 1));

                    $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    $(el.parent().parent().find('.taxes')).html(taxes);
                    $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);



                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }


                    var accountinputs = $(".accountamount");
                    var accountSubTotal = 0;
                    for (var i = 0; i < accountinputs.length; i++)
                    {
                        var currentInputValue = parseFloat(accountinputs[i].innerHTML);
                        if (!isNaN(currentInputValue))
                        {
                            accountSubTotal += currentInputValue;
                        }
                    }



                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        if (!isNaN(parseFloat(priceInput[j].value))) {
                            totalItemPrice += parseFloat(priceInput[j].value);
                        }
                    }

                    var totalItemTaxPrice = 0;
                    var itemTaxPriceInput = $('.itemTaxPrice');
                    for (var j = 0; j < itemTaxPriceInput.length; j++) {
                        if (!isNaN(parseFloat(itemTaxPriceInput[j].value))) {
                            totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                            $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount)+parseFloat(itemTaxPriceInput[j].value));
                        }
                    }

                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');
                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                        if (!isNaN(parseFloat(itemDiscountPriceInput[k].value))) {
                            totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                        }
                    }


                    $('.subTotal').html((totalItemPrice+accountSubTotal).toFixed(2));
                    $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));


                    var totalAmount= parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) + parseFloat(totalItemTaxPrice);
                    $('.totalAmount').val(totalAmount.toFixed(2));




                },
            });
        });

        $(document).on('keyup', '.quantity', function () {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();
            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);

            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice)+parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }


            var totalAccount = 0;
            var accountInput = $('.accountAmount');

            for (var j = 0; j < accountInput.length; j++) {
                if (typeof accountInput[j].value != 'undefined') {
                    var accountInputPrice = parseFloat(accountInput[j].value);

                    if (isNaN(accountInputPrice)) {
                        totalAccount = 0;
                    } else {
                        totalAccount += accountInputPrice;
                    }
                }
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var sumAmount = totalItemPrice + totalAccount;

            $('.subTotal').html((sumAmount).toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            $('.totalAmount').html((parseFloat(subTotal)+totalAccount).toFixed(2));

           //get hidden value of totalAmount
            var totalAmount= (parseFloat(subTotal)+totalAccount);
            $('.totalAmount').val(totalAmount.toFixed(2));



        })

        $(document).on('keyup change', '.price', function () {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }
            var totalItemPrice = (quantity * price)-discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice)+parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");
            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }


            var totalAccount = 0;
            var accountInput = $('.accountAmount');

            for (var j = 0; j < accountInput.length; j++) {
                if (typeof accountInput[j].value != 'undefined') {
                    var accountInputPrice = parseFloat(accountInput[j].value);

                    if (isNaN(accountInputPrice)) {
                        totalAccount = 0;
                    } else {
                        totalAccount += accountInputPrice;
                    }
                }
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            $('.subTotal').html((totalItemPrice+totalAccount).toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));
            $('.totalAmount').html((parseFloat(subTotal)+totalAccount).toFixed(2));

            //get hidden value of totalAmount
            var totalAmount= (parseFloat(subTotal)+totalAccount);
            $('.totalAmount').val(totalAmount.toFixed(2));


        })

        $(document).on('keyup change', '.discount', function () {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice)+parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');
            for (var k = 0; k < itemDiscountPriceInput.length; k++) {
                if (!isNaN(parseFloat(itemDiscountPriceInput[k].value))) {
                    totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                }
            }

            var totalAccount = 0;
            var accountInput = $('.accountAmount');

            for (var j = 0; j < accountInput.length; j++) {
                if (typeof accountInput[j].value != 'undefined') {
                    var accountInputPrice = parseFloat(accountInput[j].value);

                    if (isNaN(accountInputPrice)) {
                        totalAccount = 0;
                    } else {
                        totalAccount += accountInputPrice;
                    }
                }
            }


            // $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.subTotal').html((totalItemPrice+totalAccount).toFixed(2));

            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)+totalAccount).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));


            //get hidden value of totalAmount
            var totalAmount= (parseFloat(subTotal)+totalAccount);
            $('.totalAmount').val(totalAmount.toFixed(2));


        })

        $(document).on('keyup change', '.accountAmount', function () {

            var el1 = $(this).parent().parent().parent().parent();
            var el = $(this).parent().parent().parent().parent().parent();

            var quantityDiv = $(el.find('.quantity'));
            var priceDiv = $(el.find('.price'));
            var discountDiv = $(el.find('.discount'));

            var itemSubTotal=0;
            for (var p = 0; p < priceDiv.length; p++) {
                var quantity=quantityDiv[p].value;
                var price=priceDiv[p].value;
                var discount=discountDiv[p].value;
                if(discount.length <= 0)
                {
                    discount = 0 ;
                }
                itemSubTotal += (quantity*price) - (discount);
            }

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');

            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                var parsedValue = parseFloat(itemTaxPriceInput[j].value);

                if (!isNaN(parsedValue)) {
                    totalItemTaxPrice += parsedValue;
                }
            }


            var amount = $(this).val();
            el1.find('.accountamount').html(amount);
            var totalAccount = 0;
            var accountInput = $('.accountAmount');
            for (var j = 0; j < accountInput.length; j++) {
                totalAccount += (parseFloat(accountInput[j].value) );
            }


            var inputs = $(".accountamount");
            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {

                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            $('.subTotal').text((totalAccount+itemSubTotal).toFixed(2));
            $('.totalAmount').text((parseFloat((subTotal + itemSubTotal) + (totalItemTaxPrice))).toFixed(2));


            //get hidden value of totalAmount
            var totalAmount= (parseFloat((subTotal + itemSubTotal) + (totalItemTaxPrice)));
            $('.totalAmount').val(totalAmount.toFixed(2));


        })

        var id = '{{$Id}}';
        if (id > 0) {
            $('#vender').val(id).change();
        }

    </script>
    <script>
        $(document).on('change', '.item', function () {
            $('.item option').prop('hidden', false);
            $('.item :selected').each(function () {
                var id = $(this).val();
                if (id) {
                    $(".item option[value=" + id + "]").prop("hidden", true);
                }
            });
        });

        $(document).on('click', '[data-repeater-create]', function () {
            $('.item option').prop('hidden', false);
            $('.item :selected').each(function () {
                var id = $(this).val();
                if (id) {
                    $(".item option[value=" + id + "]").prop("hidden", true);
                }
            });
        })

        // $(document).on('click', '[data-repeater-delete]', function () {
        //     $(".price").change();
        //     $(".discount").change();

        //     $('.item option').prop('hidden', false);
        //     $('.item :selected').each(function () {
        //         var id = $(this).val();
        //         $(".item option[value=" + id + "]").prop("hidden", true);
        //     });
        // });
    </script>

    {{--  start for user select--}}
    <script>

        $(document).ready(function()
        {
            $('input[name=type]:first').prop('checked',true);
        });


        $('input[name="type"]:radio').on('change', function (e) {
            var type = $(this).val();
            if (type == 'employee') {
                $('.employee').addClass('d-block');
                $('.employee').removeClass('d-none');
                $('.customer').addClass('d-none');
                $('.customer').removeClass('d-block');
                $('.vendor').addClass('d-none');
                $('.vendor').removeClass('d-block');
                $('#employee').prop('required', true);
                $('#customer').val('').prop('required', false);
                $('#vender').val('').prop('required', false);
            }
            else if (type == 'customer') {
                $('.customer').addClass('d-block');
                $('.customer').removeClass('d-none');
                $('.employee').addClass('d-none');
                $('.employee').removeClass('d-block');
                $('.vendor').addClass('d-none');
                $('.vendor').removeClass('d-block');
                $('#employee').val('').prop('required', false);
                $('#customer').prop('required', true);
                $('#vender').val('').prop('required', false);
            }
            else {
                $('.vendor').addClass('d-block');
                $('.vendor').removeClass('d-none');
                $('.employee').addClass('d-none');
                $('.employee').removeClass('d-block');
                $('.customer').addClass('d-none');
                $('.customer').removeClass('d-block');
                $('#employee').val('').prop('required', false);
                $('#customer').val('').prop('required', false);
                $('#vender').prop('required', true);
            }
        });

        $('input[name="type"]:radio:checked').trigger('change');

        $(document).on('change', '#employee', function () {

            $('#employee_detail').removeClass('d-none');
            $('#employee_detail').addClass('d-block');
            $('#employee-box').removeClass('d-block');
            $('#employee-box').addClass('d-none');

            var cId = $(this).val();
            var url = $('#employee').data('url');

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': cId
                },
                cache: false,
                success: function (data) {

                    if (data != '') {
                        $('#employee_detail').html(data);

                    } else {
                        $('#employee-box').removeClass('d-none');
                        $('#employee-box').addClass('d-block');
                        $('#employee_detail').removeClass('d-block');
                        $('#employee_detail').addClass('d-none');
                    }

                },
            });
        });

        $(document).on('change', '#customer', function () {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $('#customer').data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function (data) {

                    if (data != '') {
                        $('#customer_detail').html(data);
                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }
                },
            });
        });

        $(document).on('change', '#vender', function () {
            $('#vender_detail').removeClass('d-none');
            $('#vender_detail').addClass('d-block');
            $('#vender-box').removeClass('d-block');
            $('#vender-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function (data) {
                    if (data != '') {
                        $('#vender_detail').html(data);
                    } else {
                        $('#vender-box').removeClass('d-none');
                        $('#vender-box').addClass('d-block');
                        $('#vender_detail').removeClass('d-block');
                        $('#vender_detail').addClass('d-none');
                    }
                },
            });
        });


        $(document).on('click', '#remove', function () {
            $('#vender-box').removeClass('d-none');
            $('#vender-box').addClass('d-block');
            $('#vender_detail').removeClass('d-block');
            $('#vender_detail').addClass('d-none');

            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');

            $('#employee-box').removeClass('d-none');
            $('#employee-box').addClass('d-block');
            $('#employee_detail').removeClass('d-block');
            $('#employee_detail').addClass('d-none');

        })

    </script>

    {{--   end for user select--}}

@endpush
@section('content')
    <div class="row">
        {{ Form::open(['url' => 'expense', 'method' => 'post']) }}
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                        {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
                        {{ Form::date('date', null, ['class' => 'form-control', 'required' => 'required']) }}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
                        {{ Form::number('amount', null, ['class' => 'form-control', 'required' => 'required', 'step' => '0.01']) }}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                        {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
            <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
        </div>
        {{ Form::close() }}
    </div>
@endsection

