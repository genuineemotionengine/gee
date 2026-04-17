#!/usr/bin/env bash
set -Eeuo pipefail

BASE_DIR="/var/lib/gee-core/renderers"
RUNTIME_BASE_DIR="/var/lib/gee-core/runtime"
RUN_DIR="/run/gee"
SNAPSERVER_CONF="/etc/snapserver.conf"
LOG_FILE="${GEE_LOG_FILE:-/var/log/gee-core-install.log}"

timestamp() {
  date '+%Y-%m-%d %H:%M:%S'
}

log() {
  local message="${1:-}"
  echo "[$(timestamp)] ${message}" | tee -a "${LOG_FILE}"
}

warn() {
  local message="${1:-}"
  echo "[$(timestamp)] [WARN] ${message}" | tee -a "${LOG_FILE}" >&2
}

fail() {
  local message="${1:-Unknown error}"
  echo "[$(timestamp)] [ERROR] ${message}" | tee -a "${LOG_FILE}" >&2
  exit 1
}

command -v systemctl >/dev/null 2>&1 || fail "systemctl is required"
command -v jq >/dev/null 2>&1 || fail "jq is required"
id mpd >/dev/null 2>&1 || fail "mpd user is required"

mkdir -p "${RUN_DIR}"
mkdir -p "${RUNTIME_BASE_DIR}"

chown root:root "${RUN_DIR}"
chmod 1777 "${RUN_DIR}" || true
chmod 775 "${RUNTIME_BASE_DIR}" || true

get_runtime_value() {
  local runtime_file="${1}"
  local jq_expr="${2}"
  jq -r "${jq_expr} // empty" "${runtime_file}"
}

normalise_stream_format() {
  local renderer_id="${1:-}"
  local stream_key="${2:-}"
  local sample_format="${3:-}"

  if [[ "${stream_key}" == "hires" && "${sample_format}" =~ ^192000: ]]; then
    echo "[$(timestamp)] Downgrading ${renderer_id}:${stream_key} from ${sample_format} to 96000:24:2 for stability" | tee -a "${LOG_FILE}" >&2
    printf '%s\n' "96000:24:2"
    return 0
  fi

  printf '%s\n' "${sample_format}"
}

build_stream_runtime_mpd_conf() {
  local renderer_dir="${1}"
  local renderer_id="${2}"
  local stream_key="${3}"
  local runtime_file="${4}"

  local runtime_conf="${renderer_dir}/mpd.${stream_key}.runtime.conf"
  local renderer_runtime_dir="${RUNTIME_BASE_DIR}/${renderer_id}"
  local stream_runtime_dir="${renderer_runtime_dir}/${stream_key}"
  local playlist_dir="${renderer_runtime_dir}/playlists"

  local mpd_port
  local sample_format
  local fifo_path
  local runtime_dir_from_json
  local canonical_conf

  mpd_port="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.mpd_port")"
  sample_format="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.format")"
  fifo_path="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.fifo_path")"
  runtime_dir_from_json="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.runtime_dir")"
  canonical_conf="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.canonical_mpd_conf")"

  [[ -n "${mpd_port}" ]] || fail "Missing mpd_port for ${renderer_id}:${stream_key}"
  [[ -n "${sample_format}" ]] || fail "Missing format for ${renderer_id}:${stream_key}"
  [[ -n "${fifo_path}" ]] || fail "Missing fifo_path for ${renderer_id}:${stream_key}"

  sample_format="$(normalise_stream_format "${renderer_id}" "${stream_key}" "${sample_format}")"

  if [[ -n "${runtime_dir_from_json}" ]]; then
    stream_runtime_dir="${runtime_dir_from_json}"
  fi

  if [[ -n "${canonical_conf}" && ! -f "${canonical_conf}" ]]; then
    fail "Canonical MPD config missing for ${renderer_id}:${stream_key} => ${canonical_conf}"
  fi

  local db_file="${stream_runtime_dir}/mpd.db"
  local state_file="${stream_runtime_dir}/mpd.state"
  local sticker_file="${stream_runtime_dir}/sticker.sql"
  local log_file="${stream_runtime_dir}/mpd.log"
  local pid_dir="/run/mpd-${renderer_id}-${stream_key}"
  local pid_file="${pid_dir}/pid"

  mkdir -p "${renderer_runtime_dir}"
  mkdir -p "${stream_runtime_dir}"
  mkdir -p "${playlist_dir}"
  mkdir -p "${pid_dir}"

  chown -R mpd:audio "${stream_runtime_dir}"
  chown -R mpd:audio "${pid_dir}"
  chown mpd:audio "${playlist_dir}" || true

  chmod 775 "${renderer_runtime_dir}" "${stream_runtime_dir}" "${playlist_dir}" "${pid_dir}" || true

  rm -f "${fifo_path}"

  cat > "${runtime_conf}" <<EOF
music_directory "/mnt/music"
playlist_directory "${playlist_dir}"
db_file "${db_file}"
log_file "${log_file}"
pid_file "${pid_file}"
state_file "${state_file}"
sticker_file "${sticker_file}"
bind_to_address "127.0.0.1"
port "${mpd_port}"
auto_update "yes"
follow_inside_symlinks "yes"
follow_outside_symlinks "yes"

audio_output {
    type "fifo"
    name "Gee ${renderer_id} ${stream_key}"
    path "${fifo_path}"
    format "${sample_format}"
    mixer_type "software"
}
EOF

  chown www-data:www-data "${runtime_conf}" || true
  chmod 664 "${runtime_conf}" || true
}

build_snapserver_conf() {
  local tmp_conf
  tmp_conf="$(mktemp)"

  cat > "${tmp_conf}" <<EOF
[server]

[http]
doc_root = /usr/share/snapweb

[stream]
buffer = 1000
chunk_ms = 20
EOF

  local renderer_dir
  for renderer_dir in "${BASE_DIR}"/*; do
    [[ -d "${renderer_dir}" ]] || continue

    local renderer_id
    local runtime_file
    renderer_id="$(basename "${renderer_dir}")"
    runtime_file="${renderer_dir}/runtime.json"

    [[ -f "${runtime_file}" ]] || fail "Missing runtime.json for renderer ${renderer_id}"

    local stream_key
    for stream_key in safe hires; do
      local fifo_path
      local sample_format

      fifo_path="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.fifo_path")"
      sample_format="$(get_runtime_value "${runtime_file}" ".streams.${stream_key}.format")"

      [[ -n "${fifo_path}" ]] || fail "Missing fifo_path for ${renderer_id}:${stream_key}"
      [[ -n "${sample_format}" ]] || fail "Missing format for ${renderer_id}:${stream_key}"

      sample_format="$(normalise_stream_format "${renderer_id}" "${stream_key}" "${sample_format}")"

      printf 'source = pipe:///%s?name=%s-%s&mode=create&sampleformat=%s&codec=flac\n' \
        "${fifo_path#/}" \
        "${renderer_id}" \
        "${stream_key}" \
        "${sample_format}" >> "${tmp_conf}"
    done
  done

  install -m 644 "${tmp_conf}" "${SNAPSERVER_CONF}"
  rm -f "${tmp_conf}"
}

stop_stale_renderer_services() {
  mapfile -t active_units < <(
    systemctl list-units --type=service --all --no-legend \
      | awk '{print $1}' \
      | grep '^mpd-renderer@.*\.service$' || true
  )

  if [[ ${#active_units[@]} -eq 0 ]]; then
    return 0
  fi

  local unit
  for unit in "${active_units[@]}"; do
    if [[ "${unit}" =~ ^mpd-renderer@[a-z0-9-]+\.service$ ]]; then
      local instance="${unit#mpd-renderer@}"
      instance="${instance%.service}"

      if [[ ! "${instance}" =~ .+-safe$ && ! "${instance}" =~ .+-hires$ ]]; then
        log "Stopping legacy renderer service ${unit}"
        systemctl stop "${unit}" || true
        systemctl disable "${unit}" >/dev/null 2>&1 || true
      fi
    fi
  done
}

restart_renderer_mpd_instances() {
  local renderer_dir
  for renderer_dir in "${BASE_DIR}"/*; do
    [[ -d "${renderer_dir}" ]] || continue

    local renderer_id
    renderer_id="$(basename "${renderer_dir}")"

    local stream_key
    for stream_key in safe hires; do
      local unit="mpd-renderer@${renderer_id}-${stream_key}.service"
      log "Enabling/restarting ${unit}"
      systemctl enable "${unit}" >/dev/null 2>&1 || true
      systemctl restart "${unit}"
    done
  done
}

main() {
  log "Regenerating Gee audio runtime from renderer registry"

  mkdir -p "${BASE_DIR}"
  mkdir -p "${RUNTIME_BASE_DIR}"
  mkdir -p "${RUN_DIR}"

  local found=0
  local renderer_dir

  for renderer_dir in "${BASE_DIR}"/*; do
    [[ -d "${renderer_dir}" ]] || continue
    found=1

    local renderer_id
    local runtime_file

    renderer_id="$(basename "${renderer_dir}")"
    runtime_file="${renderer_dir}/runtime.json"

    [[ -f "${renderer_dir}/profile.json" ]] || fail "Missing profile.json for renderer ${renderer_id}"
    [[ -f "${renderer_dir}/snapclient.conf" ]] || fail "Missing canonical snapclient.conf for renderer ${renderer_id}"
    [[ -f "${runtime_file}" ]] || fail "Missing runtime.json for renderer ${renderer_id}"
    [[ -f "${renderer_dir}/mpd.safe.conf" ]] || fail "Missing canonical mpd.safe.conf for renderer ${renderer_id}"
    [[ -f "${renderer_dir}/mpd.hires.conf" ]] || fail "Missing canonical mpd.hires.conf for renderer ${renderer_id}"

    build_stream_runtime_mpd_conf "${renderer_dir}" "${renderer_id}" "safe" "${runtime_file}"
    build_stream_runtime_mpd_conf "${renderer_dir}" "${renderer_id}" "hires" "${runtime_file}"
  done

  if [[ "${found}" -eq 0 ]]; then
    warn "No registered renderers found yet; generating empty snapserver config for fresh Geecore install"
  fi

  stop_stale_renderer_services
  build_snapserver_conf
  restart_renderer_mpd_instances

  log "Resetting failed snapserver state"
  systemctl reset-failed snapserver.service || true

  log "Restarting snapserver.service"
  systemctl restart snapserver.service

  log "Gee audio runtime regeneration completed"
}
main "$@"
