var saradnik_id;
// ### ПРИКАЗ ЛИСТЕ И ФОРМЕ ЗА ДОДАВАЊЕ САРАДНИКА ### //
$('#izmeniSaradnikaLink').click(function() //treba da se pokaze prvo padajuca lista, ne odmah sve
{
    $('#izmeniSaradnikaDiv').toggle(function()
    {
        if ($(this).css('display')=='none')
        {
            $(this).prop('hidden', 'hidden');
            window.location.reload();
        }
        else
        {
            $(this).removeProp('hidden');
            $(this).css('display','block');
        }
    });

    $("#saradnici").change(function ()
    {
        saradnik_id = $(this).find("option:selected").val();

        if( saradnik_id !== undefined || saradnik_id != null || saradnik_id > 0)
        {
            $.get("ajax/izmenaSaradnika.php?id="+saradnik_id+"",
                {id:saradnik_id},
                function (odgovor,status)
                {
                    var niz = odgovor.split("|");
                    var i= 1; // 0 je id

                    $('#izmeniSaradnika').find("input[type=text], input[type=password], input[type=email], input[type=url]").each(function(ev)
                    {

                        if(!$(this).val()  )
                        {   if(niz.indexOf("status") > -1 )
                            ++niz;

                            $(this).attr("placeholder", niz[i++]);
                        }
                    });
                });

            $("#izmeniSaradnikaForma").toggle(function ()
            {
                if ($(this).css('display') == 'none') {
                    $(this).prop('hidden', 'hidden');
                }
                else {
                    $(this).removeProp('hidden');
                    $(this).css('display', 'block');
                }
            });

        }
    });
});


// ### ПРОСЛЕЂИВАЊЕ ФОРМЕ ЗА ИЗМЕНУ САР. ### //
// прослеђивање форме //
$('#prosledi2').click(function () {
    var imeprez = $('#ime_prezime2').val();
    var kor_ime = $('#kor_ime2').val();
    var loz = $('#lozinka2').val();
    var em = $('#e_mail2').val();
    var op = $('#opis2').val();
    var st = $('select[name=status2] option:selected').val();
    var url = $('#slika_url2').val();


        if(confirm("Јесте ли сигурни да су сви подаци у реду?"))
        {
            $.post("ajax/izmenaSaradnika.php",
                {sid:saradnik_id,ime_prezime:imeprez,kor_ime:kor_ime,lozinka:loz,e_mail:em,opis:op,status:st,slika_url:url},
                function (odgovor, status)
                {
                    alert( odgovor );
                });
        }

});
// ### КРАЈ ФУНКЦИЈЕ ЗА ДОДАВАЊЕ САРАДНИКА ### //
