/**
 * Created by logos on 8.8.16..
 */



// ### ОДЈАВЉИВАЊЕ ### //
$("#odjaviSe").click(function ()
{
    if(confirm("Јесте ли сигурни? :("))
        $.get("ajax/login.php?zid=0",
            function (odgovor,status)
            {
                window.location.href = odgovor;
            });
});
// ### КРАЈ ФУНКЦИЈЕ ЗА ОДЈАВЉИВАЊЕ ### //