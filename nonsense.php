<?php
/**
 * Clase para generar textos aleatorios combinando las sílabas existentes en un determinado idioma.
 *
 * Referencias:
 *
 * Lista de sílabas del idioma español:
 * http://www.solosequenosenada.com/gramatica/spanish/listado_silabas.php
 *
 * Lista de longitudes de palabras:
 * https://books.google.com.ar/books?id=VO_oMysFYTsC&pg=PA69&lpg=PA69
 *
 * Función para generar valores aleatorios de punto flotante:
 * http://php.net/manual/en/function.mt-getrandmax.php
 *
 * Función para obtener un valor según un peso o probabilidad:
 * http://stackoverflow.com/a/10915104
 */
class Nonsense
{
    /**
     * Código del idioma.
     * @var string
     */
    protected $language;

    /**
     * Cantidad de palabras del texto.
     * @var integer
     */
    protected $words = 0;

    /**
     * Lista de sílabas con probabilidad de aparición.
     * @var array
     */
    protected $syllables = array();

    /**
     * Lista de longitudes de palabras con probabilidad de aparición.
     * @var array
     */
    protected $lengths = array();

    /**
     * Texto generado.
     * @var array	 
     */
    protected $text = array();

    /**
     * Cantidad de palabras del texto. Si el valor es 0 se genera un valor aleatorio.
     * @param integer $words
     */
    public function __construct($language, $words = 0)
    {
        $this->language = $language;
        $this->words = $words == 0 ? mt_rand(1, 50) : $words;		
        $this->loadSyllables();
        $this->loadLengths();		
    }           

    /**
     * Generar un texto aleatorio.
     * @param integer $as_array Si el valor es TRUE se obtiene un array de palabras en lugar de una cadena.
     * @return string|array
     */
    public function generate($as_array = false)
    {
        $this->text = array();
		
        $i = 0;
		
        while ($i < $this->words) {
			
            $invalid = false;			
            $word = $this->generateWord();

            // Evitar palabras consecutivas iguales.
            if ($i > 0 && $this->text[$i - 1] == $word) {
                $invalid = true;	
            }

            if (!$invalid) {
                $this->text[] = $word;
                $i++;
            }            
			
        }		
		
        $this->text[0] = ucfirst($this->text[0]);

        $i = 0;
		
        // Agregar puntos y saltos de línea para armar oraciones.
        while($i <= $this->words) {

            $words_per_sentence = mt_rand(5, 15);
            $period_position = $i + $words_per_sentence - 1;

            if (isset($this->text[$period_position])) {
                $this->text[$period_position] .= '.';
            }

            if (isset($this->text[$period_position + 1])) {
                $this->text[$period_position + 1] = ucfirst($this->text[$period_position + 1]);
            }

            $i = $period_position + 1;
        }
		
        if (strpos($this->text[$this->words - 1], '.') === false) {
            $this->text[$this->words - 1] .= '.';
        }
		
        return $as_array ? $this->text : implode(' ', $this->text);
    }
	
	/**
	 * Obtener una longitud de palabra aleatoria.
	 * @return integer
	 */	
	protected function getLength()
	{
            return $this->getByProbability($this->lengths);
	}
	
	/**
	 * Obtener una sílaba aleatoria.
	 * @return string
	 */		
	protected function getSyllable()
	{		
            return $this->getByProbability($this->syllables);
	}	

	/**
	 * Cargar los datos de un archivo CSV.
	 * @return array Se obtiene una lista de valores ordenados por probabilidad. Formato: 'probabilidad' => array(datos)
	 */	
	protected function loadFile($file)
	{
            $data = array();
            if (($handle = fopen($file, 'r')) !== false) {
                while (($line = fgetcsv($handle)) !== false) {
                    if (!isset($data[$line[1]])) {
                        $data[$line[1]] = array();
                    }
                    $data[$line[1]][] = $line[0];
                }
                fclose($handle);
            }
            ksort($data);
            return $data;
	}
	
	/**
	 * Cargar las sílabas de un idioma.
	 */	
	protected function loadSyllables()
	{
            $this->syllables = $this->loadFile($this->language . '/word-syllables.csv');			
	}
	
	/**
	 * Cargar las longitudes de palabras de un idioma.
	 */	
	protected function loadLengths()
	{
            $this->lengths = $this->loadFile($this->language . '/word-lengths.csv');	
	}
	
	/**
	 * Generar un valor aleatorio de tipo real.
	 * @return float
	 */	
	protected function randomFloat($min = 0, $max = 1) {
            return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
	
	/**
	 * Obtener un valor de una lista según valor de probabilidad o peso aleatorio.
	 * @param array $array Lista de valores ordenados por probabilidad.
	 * @return mixed
	 */	
	protected function getByProbability($array)
	{	
            $values = array_values($array);
            $weights = array_keys($array);

            $count = count($values); 
            $i = 0; 
            $n = 0;

            $num = $this->randomFloat(1, array_sum($weights));

            while ($i < $count) {
                $n += $weights[$i]; 
                if ($n >= $num) {
                    break; 
                }
                $i++; 
            }

            if (is_array($values[$i])) {
                return $values[$i][mt_rand(0, count($values[$i])-1)];
            } else {
                return $values[$i];
            }		
	}
	
	/**
	 * Generar una palabra de longitud y sílabas aleatorias.
	 */
    protected function generateWord()
    {
        $length = $this->getLength(); // cantidad de letras

        $word = array();

        $i = 0;

        do {

            $invalid = false;
            $syllable = $this->getSyllable();

            // Si se supera la longitud deseada.
            if (strlen(implode('', $word)) + strlen($syllable) > $length) {
                $invalid = true;
            }

            // Si la sílaba ya fue incluida en la palabra.
            /*if (in_array($syllable, $word)) {
                    $invalid = true;
            }*/

            // Si la última letra de la sílaba anterior es igual a la primera letra de la sílaba nueva.
            if ($i > 0 && substr($word[$i-1], -1) == $syllable[0]) {
                $invalid = true;
            }

            if (!$invalid) {
                $word[] = $syllable;
                $i++;
            }

        } while($invalid || strlen(implode('', $word)) < $length);
		
        return implode('', $word);
    }
}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$nonsense = new Nonsense('es', 200);
$text = str_replace('. ', '.<br>', $nonsense->generate());
?>
<p style="margin:50px 100px; font-size: 20px;">

<?php print $text;?>

</p>