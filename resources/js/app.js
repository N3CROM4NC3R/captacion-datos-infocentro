import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Read the payload the diagnosis view publishes on window.
 *
 * It is read lazily, when a component initialises, because the Vite entry is a
 * deferred module while the payload is written by an inline script.
 */
const datos = () =>
    window.datosFicha ?? {
        urls: {},
        seleccion: {},
        municipios: {},
        parroquias: {},
        facilitadores: [],
        vinculos: [],
        estadosProyecto: {},
        problemas: [],
        actividades: {},
        niveles: {},
        errores: {},
        vinculacionMincyt: false,
        acompFundacite: false,
        cienciaCiudadana: false,
        proveedor: '',
        operatividad: '',
        horarioSuficiente: '',
        vinculacionSac: '',
        vinculacionConsejos: '',
        vinculacionAutoridad: false,
        tieneFacilitadorTitular: false,
        consultasPopulares: '',
        ofertaAnual: '',
        suficienciaEquipo: '',
    };

const opciones = (filas) => Object.fromEntries(filas.map((fila) => [fila.id, fila.nombre]));

const pedir = async (url, parametro, valor) => {
    const completa = new URL(url, window.location.origin);

    completa.searchParams.set(parametro, valor);

    const respuesta = await fetch(completa, {
        headers: { Accept: 'application/json' },
    });

    return respuesta.json();
};

const facilitadorVacio = () => ({
    nombre: '',
    nivel_academico: '',
    titulo_carrera: '',
    anios_experiencia: '',
    formacion_tic: false,
    formacion_tic_detalle: '',
    formacion_ciencias: false,
    formacion_ciencias_area: '',
    horas_semanales: '',
});

const vacioVinculacion = () => ({
    institucion: '',
    proyecto: '',
    estado: '',
    ultimo_contacto: '',
});

/**
 * Each of these mirrors a `requiere…()` helper on the matching PHP enum, so the
 * conditional field the user sees matches what validation will enforce.
 */
const requiereNombre = (proveedor) =>
    proveedor === 'empresa_privada' || proveedor === 'red_comunitaria';

const requiereDetalle = (operatividad) =>
    operatividad === 'con_limitaciones' || operatividad === 'no_operativo';

const requiereMotivo = (suficiencia) =>
    suficiencia === 'no' || suficiencia === 'parcialmente';

// Cada helper replica una regla del enum PHP equivalente, para que el campo
// condicional que ve la persona coincida con lo que la validación va a exigir.
const vinculacionActiva = (valor) => valor === 'activa' || valor === 'pasiva';

const consultasConDetalle = (valor) => valor === 'activa' || valor === 'limitada';

const ofertaDefinida = (valor) => valor === 'si' || valor === 'parcial';

const suficienciaFalta = (valor) => valor === 'falta';

document.addEventListener('alpine:init', () => {
    /**
     * Cascading Estado -> Municipio -> Parroquia selects, plus the conditional
     * fields of sections B and C.
     */
    Alpine.data('perfilamiento', () => {
        const config = datos();
        const puntuaciones = config.puntuaciones ?? {};

        // Cada dimensión es una propiedad propia y no una clave de objeto:
        // `x-model` se compila como destino de una asignación, así que
        // `puntuaciones['conectividad']` no es un destino válido y Alpine
        // lanza un SyntaxError que deja la sección entera sin pintar.
        return {
            puntuaciones,
            tipos: config.tipos ?? {},

            get total() {
                return Object.values(this.puntuaciones).reduce(
                    (suma, valor) => suma + (Number(valor) || 0),
                    0,
                );
            },

            puntuacionConectividad: String(puntuaciones.conectividad ?? ''),
            puntuacionOperatividad: String(puntuaciones.operatividad ?? ''),
            puntuacionFacilitadores: String(puntuaciones.facilitadores ?? ''),
            puntuacionEquipamiento: String(puntuaciones.equipamiento ?? ''),
            puntuacionArticulacionComunitaria: String(puntuaciones.articulacion_comunitaria ?? ''),
            puntuacionOfertaFormativa: String(puntuaciones.oferta_formativa ?? ''),
            puntuacionVinculacionMincyt: String(puntuaciones.vinculacion_mincyt ?? ''),

            // Copia las propiedades enlazadas a los `x-model` —que son las que
            // el usuario mueve— al objeto que suma el total. Sin esto el
            // desplegable y el total cuentan cosas distintas.
            recalcular() {
                this.puntuaciones = {
                    conectividad: this.puntuacionConectividad,
                    operatividad: this.puntuacionOperatividad,
                    facilitadores: this.puntuacionFacilitadores,
                    equipamiento: this.puntuacionEquipamiento,
                    articulacion_comunitaria: this.puntuacionArticulacionComunitaria,
                    oferta_formativa: this.puntuacionOfertaFormativa,
                    vinculacion_mincyt: this.puntuacionVinculacionMincyt,
                };
            },

            // Los umbrales vienen del enum, en el mismo orden que la cadena
            // desdePuntuacion(): el primero que alcanza el total es el suyo.
            get tipoActual() {
                return (
                    Object.entries(this.tipos).find(
                        ([, tipo]) => this.total >= tipo.desde,
                    )?.[0] ?? 'c'
                );
            },
        };
    });

    Alpine.data('fichaDiagnostico', () => {
        const config = datos();

        return {
            estadoId: config.seleccion.estado ?? '',
            municipioId: config.seleccion.municipio ?? '',
            parroquiaId: config.seleccion.parroquia ?? '',
            municipios: config.municipios,
            parroquias: config.parroquias,
            proveedor: config.proveedor ?? '',
            estadoOperativo: config.operatividad ?? '',
            horarioSuficiente: config.horarioSuficiente ?? '',
            vinculacionSac: config.vinculacionSac ?? '',
            vinculacionConsejos: config.vinculacionConsejos ?? '',
            vinculacionAutoridad: config.vinculacionAutoridad ?? false,
            tieneTitular: config.tieneFacilitadorTitular ?? false,
            consultasPopulares: config.consultasPopulares ?? '',
            ofertaAnual: config.ofertaAnual ?? '',

            // Estado de cada tipo de actividad: al marcar «Sí» se muestran sus
            // campos de frecuencia, participantes y detalle sin recargar.
            actividades: config.actividades ?? {},
            suficienciaEquipo: config.suficienciaEquipo ?? '',
            activo: config.estatusActivo ?? '1',

            get proveedorNecesitaNombre() {
                return requiereNombre(this.proveedor);
            },

            get operatividadNecesitaDetalle() {
                return requiereDetalle(this.estadoOperativo);
            },

            get horarioNecesitaMotivo() {
                return requiereMotivo(this.horarioSuficiente);
            },

            get vinculacionSacVinculada() {
                return vinculacionActiva(this.vinculacionSac);
            },

            get vinculacionConsejosVinculada() {
                return vinculacionActiva(this.vinculacionConsejos);
            },

            get consultasPopularesDetalle() {
                return consultasConDetalle(this.consultasPopulares);
            },

            get ofertaAnualDefinida() {
                return ofertaDefinida(this.ofertaAnual);
            },

            get suficienciaFalta() {
                return suficienciaFalta(this.suficienciaEquipo);
            },

            async cargarMunicipios() {
                this.municipioId = '';
                this.parroquiaId = '';
                this.parroquias = {};

                if (! this.estadoId) {
                    this.municipios = {};

                    return;
                }

                this.municipios = opciones(
                    await pedir(config.urls.municipios, 'estado_id', this.estadoId),
                );
            },

            async cargarParroquias() {
                this.parroquiaId = '';
                this.parroquias = {};

                if (! this.municipioId) {
                    return;
                }

                this.parroquias = opciones(
                    await pedir(config.urls.parroquias, 'municipio_id', this.municipioId),
                );
            },
        };
    });

    /**
     * Repeatable facilitator blocks driven by the "cantidad" input.
     *
     * La ficha no pregunta "¿tiene facilitadores?", pide directamente el número
     * total, así que la cantidad manda sobre los bloques.
     */
    Alpine.data('facilitadores', () => {
        const config = datos();

        return {
            filas: config.facilitadores,
            niveles: config.niveles,
            errores: config.errores,

            get cantidad() {
                return this.filas.length;
            },

            set cantidad(valor) {
                const objetivo = Math.min(50, Math.max(0, parseInt(valor, 10) || 0));

                while (this.filas.length < objetivo) {
                    this.filas.push(facilitadorVacio());
                }

                if (this.filas.length > objetivo) {
                    this.filas = this.filas.slice(0, objetivo);
                }
            },

            eliminar(indice) {
                this.filas.splice(indice, 1);
            },
        };
    });

    /**
     * Repeatable MINCYT institution blocks (H.2).
     *
     * El número de bloques lo fija el contador, igual que en los facilitadores
     * de la sección D. El PDF no trae ese contador: propone tres bloques fijos.
     */
    Alpine.data('vinculacionesMincyt', () => {
        const config = datos();

        return {
            vinculado: config.vinculacionMincyt ?? false,
            fundacite: config.acompFundacite ?? false,
            filas: (config.vinculos ?? []).filter(Boolean),
            estados: config.estadosProyecto ?? {},
            errores: config.errores ?? {},

            get cantidad() {
                return this.filas.length;
            },

            set cantidad(valor) {
                const objetivo = Math.min(20, Math.max(0, parseInt(valor, 10) || 0));

                while (this.filas.length < objetivo) {
                    this.filas.push(vacioVinculacion());
                }

                if (this.filas.length > objetivo) {
                    this.filas = this.filas.slice(0, objetivo);
                }
            },

            get sinInstituciones() {
                // Pode haber filas abiertas por el contador que aún no se han
                // rellenado, así que «no hay vinculación» se deduce de que
                // ninguna tenga el nombre informado.
                return this.filas.every((fila) => !fila.institucion);
            },

            eliminar(indice) {
                this.filas.splice(indice, 1);
            },
        };
    });

    /**
     * I.1 multiselect plus the two Sí/No follow-ups of section I.
     */
    /**
       * Modal de eliminar un infocentro del listado.
       *
       * El detalle de cada fila llega indexado por id, en un único bloque de
       * JSON, para no dejar quince copias del marcado del modal en el HTML. Los
       * filtros salen de la URL de la propia página: así el botón y el borrado
       * devuelven al mismo sitio con el mismo filtro puesto.
       */
      Alpine.data('eliminarInfocentro', (detalle, base) => {
          const filtros = ['q', 'ficha', 'page'];

          return {
              detalle,
              objetivo: null,

              /** The listing this was opened from, for Cancel. */
              get pagina() {
                  return window.location.pathname + window.location.search;
              },

              /** The DELETE target for whoever is in the modal right now. */
              get action() {
                  const destino = new URL(base + (this.objetivo?.id ?? ''), window.location.origin);

                  filtros.forEach((clave) => {
                      const valor = new URLSearchParams(window.location.search).get(clave);

                      if (valor !== null) destino.searchParams.set(clave, valor);
                  });

                  return destino.toString();
              },

              abrir(id) {
                  this.objetivo = this.detalle[id] ? { id, ...this.detalle[id] } : null;

                  // El modal de Breeze solo se abre con su evento `open-modal`,
                  // así que hay que despacharlo aquí: sin esto el contenido se
                  // carga en la página pero nunca se ve.
                  this.$dispatch('open-modal', 'eliminar-infocentro');
              },
          };
      });

      Alpine.data('necesidades', () => {
        const config = datos();

        return {
            problemas: config.problemas ?? [],
            cienciaCiudadana: config.cienciaCiudadana ?? false,
        };
    });
});

Alpine.start();
