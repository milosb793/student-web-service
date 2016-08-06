<?php
require_once "class.Izuzetak.php";

class Saradnik extends Korisnik
{
    //има све атрибуте као и корисник
    private $opis;
    private $status;
    private $slika_url;

    /**
     * Saradnik constructor.
     * @param $korisnik_id
     * @param $kor_ime
     * @param $lozinka
     * @param $ime_prezime
     * @param $e_mail
     * @param $opis
     * @param $status
     * @param $slika_url
     */
    public function __construct($korisnik_id, $kor_ime, $lozinka, $ime_prezime, $e_mail, $opis, $status, $slika_url)
    {
        parent::__construct($korisnik_id, $kor_ime, $lozinka, $ime_prezime, $e_mail);
        $this->opis = $opis;
        $this->status = $status;
        $this->slika_url = $slika_url;
    }

    /**
     * Магична метода, претвара објекат у стринг
     * @return string
     */
    public function __toString()
    {
        return parent::__toString() . "\nОпис: " . $this->opis . "\nСтатус: " . $this->status .
        "\nУРЛ слике: " . $this->slika_url . "\n";
    }

    /**
     * @param $name
     * @param $value
     * @throws Izuzetak
     */
    public function __set($name, $value)
    {
        if (property_exists("Saradnik", $this->$name))
            $this->$name = $value;
        else
            throw new Izuzetak("Не постоји такав параметар!");
    }

    /**
     * @param $name
     * @return string
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : "";
    }

    /**
     * Имплементирати по потреби
     */
    public function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function dodajMaterijal($lab_vezba_id, $naziv, $lokacija)
    {
        //реализовати
    }

    /**
     * @param $opis
     * @param $datum_od
     * @param $br_lab
     */
    public static function dodajLabVezbu($opis, $datum_od, $br_lab,$drugi_saradnik_id=0)
    {
        ### потребно је ажурирати најпре табелу lab_vezba, затим табелу laboratorija, затим табелу материјал;
        #   треба проверити и да ли та вежба већ постоји.
        #   мислим да би најбољи приступ уносу био попуњавање форме и слање података на страницу лаб. вежба
        #   материјали се исто убацују путем форме за датотеке


        if (isset($opis) && isset($datum) && isset($br_lab))
        {
            //saradnik_id ћемо узети из сесије
            $saradnik = unserialize($_SESSION['korisnik']);
            $saradnik_id = $saradnik->korisnik_id;

            //конвертовање датума у валидан облик за базу
            $datum = date("DD/MM/YYYY", strtotime($datum_od));

            //провера да ли постоји вежба
            $lab_vezbe = Lab_vezba::procitajSve();

            foreach ($lab_vezbe as $vezba)
            {
                if (($vezba["opis"] == $opis) && ($vezba["datum_odrzavanja"] == $datum))
                {
                    throw new Izuzetak("Лаб. вежба са тим описом и датумом одржавања већ постоји!");
                }
                else if (($br_lab >= Lab_vezba::BROJ_LAB_MIN) && ($br_lab <= Lab_vezba::BROJ_LAB_MAX))
                {
                    throw new Izuzetak("Неисправан број лабараторије. Број мора бити у опсегу од: " .
                        Lab_vezba::BROJ_LAB_MIN . " до " . Lab_vezba::BROJ_LAB_MAX . ".");
                }
                else
                {   //ажурирање табеле лаб.вежба
                        # провера да сарадник не додаје другог сарадника на вежбу

                    if($drugi_saradnik_id!=0)
                    {
                        $sar_id = $drugi_saradnik_id;
                        ## провера да ли је овај сарадник сарадник на предмету, у табели предмет-сарадник, али нам треба предмет_ид
                            //предмет_ид узимамо из табеле предмет-сарадник
                        $predmet_id = trim(Baza::vratiInstancu()->select("SELECT predmet_id FROM predmet_saradnik WHERE saradnik_id='".$sar_id."';"));
                        if(!$predmet_id ) //ако не постоји, излазимо из методе са грешком
                            throw new Izuzetak("Унети сарадник није сарадник на предмету.");
                    }
                    else
                        $sar_id = $saradnik_id;


                    $promenjeno_redova1 = Baza::vratiInstancu()->inUpDel("INSERT INTO lab_vezba(saradnik_id,opis,datum_odrzavanja)
                                                                        VALUES('" . $sar_id . "', '" . $opis . "', '" . $datum . "');");

                    //ажурирање табеле лабораторија
                        #узимамо лаб_вежба_ид који је тек додат у табелу

                    $lab_vezba_id = Baza::vratiInstancu()->select("SELECT lab_vezba_id FROM lab_vezba WHERE saradnik_id='" . $sar_id . "'" .
                        " AND opis='" . $opis . "' AND datum_odrzavanja ='" . $datum . "' ;");

                    $promenjeno_redova2 = Baza::vratiInstancu()->inUpDel("INSERT INTO laboratorija(lab_vezba_id,saradnik_id,broj_lab)
                                                                        VALUES('" . trim($lab_vezba_id) . "', '" . $sar_id . "', '" . $br_lab . "');");

                    //убацивање у табелу материјали
                    $promenjeno_redova3 = 1;

                    if ($promenjeno_redova1 > 0 && $promenjeno_redova2 > 0 && $promenjeno_redova3 > 0)
                        Metode::obavestenje("База је успешно ажурирана!");
                    else
                        throw new Izuzetak("Дошло је до грешке. Лаб. вежба није уписана!");
                }
            }
        }
    } // реализовати додавање у табелу материјал

    /**
     * Метода за измену постојеће вежбе. За детерминисање постојеће лаб вежбе користе се
     * прва три параметра. Друга три су опциона, и проверава се да си су постављена један по један.
     * Прослеђују се параметри из форме.
     * @param $opis
     * @param $datum
     * @param $br_lab
     * @param string $novi_opis
     * @param string $novi_datum
     * @param int $novi_br_lab
     */
    public static function izmeniLabVezbu($opis, $datum, $br_lab, $novi_opis = "", $novi_datum = "", $novi_br_lab = 0)
    {
        $rezultat = [];
        $status1 = null;
        $status2 = null;
        $status3 = null;

        //провера да ли постоји вежба са прва три параметара, коју желимо да изменимо
        $upit = "SELECT * FROM lab_vezba WHERE opis='" . $opis . "' AND datum_odrzavanja='" . $datum . "' AND lab_vezba_id =" .
            "(SELECT lab_vezba_id FROM laboratorija WHERE br_lab='" . $br_lab . "');";

        if ($rezultat = mysqli_fetch_assoc(Baza::vratiInstancu()->select($upit))) //ако постоји овакав запис у бази, тј. ако постоји вежба, вршимо промену
        {
            if ($novi_opis != "") {
                $status1 = Baza::vratiInstancu()->inUpDel("UPDATE lab_vezba SET opis='" . $novi_opis . "' WHERE lab_vezba_id='" . $rezultat["lab_vezba_id"] . "' ;");
            }
            if ($novi_datum != "") {
                $status2 = Baza::vratiInstancu()->inUpDel("UPDATE lab_vezba SET datum_odrzavanja='" . $novi_datum . "' WHERE lab_vezba_id='" . $rezultat["lab_vezba_id"] . "' ;");

            }
            if ($novi_br_lab != 0) {
                $saradnik_id = unserialize($_SESSION["korisnik"])->korisnik_id;

                $status3 = Baza::vratiInstancu()->inUpDel("UPDATE laboratorija SET br_lab='" . $novi_br_lab . "' WHERE lab_vezba_id='" . $rezultat["lab_vezba_id"] . "' AND saradnik_id='" . $saradnik_id . "' ;");

            }

            //провера да ли има измена
            if ($novi_opis != "") {
                if ($novi_datum != "") {
                    if ($novi_br_lab != "") {
                        if ($status1 > 0 && $status2 > 0 && $status3 > 0)
                            Metode::obavestenje("Лаб. вежба је успешно ажурирана!");
                        else
                            Metode::obavestenje("Дошло је до грешке приликом ажурирања!");
                    } else {
                        if ($status1 > 0 && $status2 > 0)
                            Metode::obavestenje("Лаб. вежба је успешно ажурирана!");
                        else
                            Metode::obavestenje("Дошло је до грешке приликом ажурирања!");
                    }
                } else {
                    if ($status1 > 0)
                        Metode::obavestenje("Лаб. вежба је успешно ажурирана!");
                    else
                        Metode::obavestenje("Дошло је до грешке приликом ажурирања!");
                }
            }
        } else
            throw new Izuzetak("Не постоји лаб. вежба са тим параметрима.");


    }

    /**
     * @param $opis
     * @param $datum
     * @param $br_lab
     */
    public static function obrisiLabVezbu($opis, $datum, $br_lab) //проверити да ли ради и како каскадно брисање, иначе треба обрисати и из лабораторије и материјала
    {
        //провера да ли постоји вежба са прва три параметара, коју желимо да обришемо
        $upit = "SELECT * FROM lab_vezba WHERE opis='" . $opis . "' AND datum_odrzavanja='" . $datum . "' AND lab_vezba_id =" .
                     "(SELECT lab_vezba_id FROM laboratorija WHERE br_lab='" . $br_lab . "');";

        if ($rezultat = mysqli_fetch_assoc(Baza::vratiInstancu()->select($upit))) //ако постоји овакав запис у бази, тј. ако постоји вежба, вршимо промену
        {
            $lab_vezba_id = trim($rezultat["lab_vezba_id"]);
            $saradnik_id = unserialize($_SESSION["korisnik"])->korisnik_id;

           $vrednost[0] =  Baza::vratiInstancu()->inUpDel("DELETE FROM lab_vezba WHERE lab_vezba_id='".$lab_vezba_id."' AND saradnik_id='".$saradnik_id."' ;");

           if($vrednost[0] > 0 )
               Metode::obavestenje("Успешно сте обрисали лаб. вежбу. ");
            else
                throw new Izuzetak("Дошло је до грешке, лаб вежба није обрисана.");

        }
        else
            throw new Izuzetak("Не постоји таква лаб. вежба.");
    }

    /**
     * @param $saradnik_id
     * @param $nova_bio
     * @throws Izuzetak
     */
    public static function promeniBio($saradnik_id,$nova_bio)
    {
        if(Baza::vratiInstancu()->inUpDel("UPDATE saradnik SET opis='".$nova_bio."' WHERE saradnik_id='". $saradnik_id."' ;"))
            Metode::obavestenje("Успешно сте измении биографију.");
        else
            throw new Izuzetak("Дошло је до грешке при измени биографије. ");
    }

    /**
     * @param $saradnik_id
     * @param $stara
     * @param $nova
     * @param $nova_ponovo
     * @throws Izuzetak
     */
    public static function promeniLozinku($saradnik_id,$stara,$nova,$nova_ponovo)
    {
        if($stara!=$nova)
            if($nova==$nova_ponovo)
            {
                if(Baza::vratiInstancu()->inUpDel("UPDATE saradnik SET lozinka='".$nova."' WHERE saradnik_id='".$saradnik_id."' ;") )
                    Metode::obavestenje("Успешно сте променили лозинку!");
                else
                    throw new Izuzetak("Дошло је до грешке при ажурирању базе. Покушајте поново. ");
            }
        else
            throw new Izuzetak("Лозинке се не подударају. Проверите унос.");
    else
        throw new Izuzetak("Нова лозинка мора бити различита од старе. Покушајте поново.");

    }

    public static function promeniSliku($saradnik_id)
    {
        // реализовати
    }
    
    public static function izlistajSvePredmete()
    {
        
    }

    public static function daLiJeSaradnik()
    {
        if(!isset($_SESSION['korisnik']['admin_id']) )
        {
            self::preusmeri("login.php");
            echo "Да бисте приступили овом делу сајта, морате бити улоговани." ;
        }
    }




}