
$(document).ready(function () {

  let $search_results = $('.search-form .search-result ul')
  let $search_box = $('.search-form input.search-box')

  $('.search-icon').click(function (e) {
    e.preventDefault()
    $('.search-form').fadeToggle().find('input[type=text]').focus()
  })

  $('.search-form .close-btn').click(function () {
    $(this).closest('form').fadeOut()

    $search_box.val('')
    $search_results.empty()

  })

  let debounce = null

  $search_box.on('keyup', function () {

    let query = $(this).val()

    clearTimeout(debounce)

    if (query === '') {

      $search_results.empty()

    } else {

      debounce = setTimeout(function () {

        $.ajax({
          url: mainurl + "/productSearch/",
          data: { 'q': query },
          success(res) {

            $search_results.empty()

            // console.log(res);
            var res = JSON.parse(res);
            //console.log(obj);

            $.each(res, function (index, product) {


              $search_results.append('<li>' +
                '<a href="' + mainurl + '/item/' + product.slug + '">' +
                '<img src="' + mainurl + '/assets/images/thumbnails/' + product.thumbnail + '" alt="No Image">' +
                '<div>' +
                '<p class="title">' + product.name + '</p>' +
                '<p class="price">Price: ' + product.price + ' TK</p>' +
                '</div>' +
                '</a>' +
                '</li>')
            })// end foreach 
          } // end if success



        })
      }, 350)


    }

  })

})


