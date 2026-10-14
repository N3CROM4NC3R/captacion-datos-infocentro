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
            consultasPopulares: config.consultasPopulares ?? '',
            ofertaAnual: config.ofertaAnual ?? '',
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
     * Repeatable MINCYT institution blocks (H.2). No hay tope: el PDF propone
     * tres pero el formulario deja agregar más.
     */
    Alpine.data('vinculacionesMincyt', () => {
        const config = datos();

        return {
            vinculado: config.vinculacionMincyt ?? false,
            fundacite: config.acompFundacite ?? false,
            filas: (config.vinculos ?? []).filter(Boolean).length
                ? config.vinculos.filter(Boolean)
                : [vacioVinculacion()],
            estados: config.estadosProyecto ?? {},
            errores: config.errores ?? {},

            agregar() {
                this.filas.push(vacioVinculacion());
            },

            eliminar(indice) {
                this.filas.splice(indice, 1);
            },
        };
    });

    /**
     * I.1 multiselect plus the two Sí/No follow-ups of section I.
     */
    Alpine.data('necesidades', () => {
        const config = datos();

        return {
            problemas: config.problemas ?? [],
            cienciaCiudadana: config.cienciaCiudadana ?? false,
        };
    });
});

Alpine.start();
