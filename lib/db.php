<?php
	/* Biblioteca para el analizador de Markdown */
	include "../lib/Parsedown.php";

	/**
	 * Obtiene el artículo con el id especificado.
	 *
	 * @param id_art
	 *		ID del artículo a obtener.
	 *
	 * @return
	 *		El texto del artículo, si se
	 *	ha encontrado; o un texto avisando del
	 *	error que se haya producido.
	 */
	function obtener_art ($id_art)
	{
		/* Obtiene los datos para la conexión del fichero 'datos-con_bd.json' */
		$datos = json_decode (file_get_contents ('datos-con_bd.json'), true);

		$bd = $datos ["bd"];
		$host = $datos ["host"];
		$usuario = $datos ["usuario"];
		$contr = $datos ["contr"];

		$texto = "## No se ha encontrado el artículo especificado";
		$conn = pg_connect ("host=$host dbname=$bd user=$usuario password=$contr");

		if (!$conn)
		{
			pg_close ();
			return "Error al conectarse a la base de datos.";
		}

		/* Prepara y ejecuta la consulta */
		$consulta = pg_prepare ($conn, "ver_art", "SELECT * FROM articulos WHERE id_articulo = $1");
		$consulta = pg_execute ($conn, "ver_art", array ($id_art));

		/* Si se ha encontrado, se carga el texto */
		if (!$consulta || pg_num_rows ($consulta) != 1)
		{
			$texto =  "## No se ha encontrado el artículo especificado";
		}
		else
		{
			$articulo = pg_fetch_array ($consulta);
			$texto = $articulo["texto"];
		}

		$Parsedown = new Parsedown ();

		$texto = $Parsedown->text ($texto);

		pg_close ($conn);
		return $texto;
	}

	/**
	 * Obtiene la cuenta del usuario especificado.
	 *
	 * @param nombre
	 *		Nombre del usuario cuya contraseña se desea obtener.
	 *
	 * @return
	 *		Array con los campos de la tupla resultado (si
	 *	existe) de la tabla 'usuarios': ['nombre', 'pass'];
	 *	o null si ha habido algún problema.
	 */
	function obtener_cuenta ($nombre)
	{
		/* Obtiene los datos para la conexión del fichero 'datos-con_bd.json' */
		$datos = json_decode (file_get_contents ('datos-con_bd.json'), true);

		$bd = $datos ["bd"];
		$host = $datos ["host"];
		$usuario = $datos ["usuario"];
		$contr = $datos ["contr"];

		$tupla = null;
		$conn = pg_connect ("host=$host dbname=$bd user=$usuario password=$contr");

		if (!$conn)
		{
			pg_close ();
			return null;
		}

		/* Prepara y ejecuta la consulta */
		$consulta = pg_prepare ($conn, "ver_pass", "SELECT * FROM usuarios WHERE nombre = $1");
		$consulta = pg_execute ($conn, "ver_pass", array ($nombre));

		/* Si se ha encontrado, se carga el nombre de usuario */
		if ($consulta && pg_num_rows ($consulta) == 1)
		{
			$tupla = pg_fetch_array ($consulta);
		}

		pg_close ($conn);
		return $tupla;
	}

	/**
	 * Añade un nuevo usuario a la base de datos.
	 *
	 * @param nombre
	 *		Nombre de la cuenta. Clave primaria (debe ser único).
	 *
	 * @param pass
	 *		Contraseña para la cuenta. Se debe proporcionar en
	 *	 texto plano para ser tratada (hasheada) en esta función.
	 *
	 *
	 * @return
	 *		True si la tupla se añadió correctamente, o False
	 *	si hubo algún problema.
	 */
	function insertar_cuenta ($nombre, $pass)
	{
		/* Obtiene los datos para la conexión del fichero 'datos-con_bd.json' */
		$datos = json_decode (file_get_contents ('datos-con_bd.json'), true);

		$bd = $datos ["bd"];
		$host = $datos ["host"];
		$usuario = $datos ["usuario"];
		$contr = $datos ["contr"];

		$conn = pg_connect ("host=$host dbname=$bd user=$usuario password=$contr");

		if (!$conn)
		{
			pg_close ();
			return False;
		}

		/* Genera un id de usuario aleatorio */
		$id = rand ();

		$consulta = pg_prepare ($conn, "ver_uid", "SELECT * FROM usuarios WHERE id = $1");
		pg_execute ($conn, "ver_uid", array ($id));

		while (pg_num_rows () > 0)
		{
			$id++;

			$consulta = pg_prepare ($conn, "ver_uid", "SELECT * FROM usuarios WHERE id = $1");
			pg_execute ($conn, "ver_uid", array ($id));	
		}

		/* Intenta insertar los datos */
		$datos = array ("nombre" => $nombre, "pass" => password_hash ($pass, PASSWORD_DEFAULT), "uid" => $id);
		$resultado = pg_insert ($conn, "usuarios", $datos);

		if (!$resultado)
		{
			pg_close ();
			return False;
		}

		pg_close ($conn);
		return True;
	}

	/**
	 * Añade un archivo a la tabla "archivos" de la base de datos.
	 *
	 * @param usuario
	 *		Nombre del usuario propietario.
	 *
	 * @param datos
	 *		Datos del archivo.
	 *
	 * @param descr
	 *		Descripción del archivo (nombre, contenido...).
	 *
	 * @param permisos
	 *		Byte con los permisos de lectura y escritura. El formato
	 *	es el siguiente (empezando por el bit de mayor peso):
	 *		-> Permiso lectura usuario (UID)
	 *		-> Permiso escritura usuario (UID)
	 *
	 *		-> Permiso lectura grupo (GID)
	 *		-> Permiso escritura usuario (GID)
	 *
	 *		-> Permiso lectura rest de usuarios
	 *		-> Permiso escritura resto de usuarios
	 *
	 *	De modo gráfico: rw rw rw
	 *			 ^   ^  ^
	 *			 |   |  |
	 *			uid gid resto
	 *
	 * @return True si se han insertado los datos correctamente; o False si no.
	 */
	function insertar_archivo ($usuario, $datos, $descr, $permisos)
	{
		/* Obtiene los datos para la conexión del fichero 'datos-con_bd.json' */
		$datos = json_decode (file_get_contents ('datos-con_bd.json'), true);

		$bd = $datos ["bd"];
		$host = $datos ["host"];
		$usuario = $datos ["usuario"];
		$contr = $datos ["contr"];

		$conn = pg_connect ("host=$host dbname=$bd user=$usuario password=$contr");

		if (!$conn)
		{
			pg_close ();
			return False;
		}

		/* Intenta insertar los datos */
//		$datos = array ("nombre" => $nombre, "pass" => password_hash ($pass, PASSWORD_DEFAULT));
//		$resultado = pg_insert ($conn, "usuarios", $datos);

//		if (!$resultado)
//		{
//			pg_close ();
//			return False;
//		}

		pg_close ($conn);
		return True;
	}

?>
