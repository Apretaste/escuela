<h1>No has conseguido pasar el curso</h1>

<p>Solo has alcanzado {$course->calification} de 80 puntos necesarios, y por lo tanto aun no puedes pasar este curso. Puedes volver a estudiar y tomar la prueba nuevamente.</p>

{space5}

<center>
	{button href="ESCUELA REPETIR {$course->id}" caption="Repetir" color="red"}
	{button href="ESCUELA CURSO {$course->id}" caption="Indice" color="grey"}
</center>
