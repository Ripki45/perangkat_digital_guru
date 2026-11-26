import { maskLast10Digits, RDM_SEMESTERS, RDM_SUBJECTS_MI } from "@/types/emis";
import type {
    EmisMadrasahProfile,
    EmisStudent,
    RdmScoresMap,
} from "@/types/emis";

export function escapeHtml(str: string | number | null | undefined): string {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

export function buildOfficialKopHtml(profile: EmisMadrasahProfile): string {
    const kiriHtml = profile.logoKiri
        ? `<img src="${escapeHtml(profile.logoKiri)}" alt="Logo Kiri" style="width:68px;height:68px;object-fit:contain;">`
        : `<div style="width:64px;height:64px;border:1px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:9px;color:#94a3b8;text-align:center;">LOGO<br>KEMENAG</div>`;
    const kananHtml = profile.logoKanan
        ? `<img src="${escapeHtml(profile.logoKanan)}" alt="Logo Kanan" style="width:68px;height:68px;object-fit:contain;">`
        : `<div style="width:64px;height:64px;"></div>`;

    return `
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:10px;font-family:'Times New Roman',Times,serif;color:#000;">
            <div style="flex-shrink:0;">${kiriHtml}</div>
            <div style="flex:1;text-align:center;line-height:1.25;">
                <div style="font-size:12px;font-weight:bold;text-transform:uppercase;">KEMENTERIAN AGAMA REPUBLIK INDONESIA</div>
                <div style="font-size:12px;font-weight:bold;text-transform:uppercase;">${escapeHtml(profile.kemenagKab)}</div>
                <div style="font-size:16px;font-weight:bold;text-transform:uppercase;margin:2px 0;">${escapeHtml(profile.namaMadrasah)}</div>
                <div style="font-size:10px;">NSM: ${escapeHtml(profile.nsm)} | NPSN: ${escapeHtml(profile.npsn)}</div>
                <div style="font-size:10px;">${escapeHtml(profile.alamat)}</div>
                <div style="font-size:10px;">Website: ${escapeHtml(profile.website)} | Email: ${escapeHtml(profile.email)}</div>
            </div>
            <div style="flex-shrink:0;">${kananHtml}</div>
        </div>
    `;
}

export function buildBukuIndukSheetHtml(
    s: EmisStudent,
    profile: EmisMadrasahProfile,
): string {
    return `
        <div class="buku-induk-a4 page-break bg-white mx-auto w-full max-w-[210mm] p-6 shadow border border-slate-300 space-y-3 text-slate-900">
            ${buildOfficialKopHtml(profile)}
            <div class="border-b-2 border-emerald-800 pb-2.5 flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-sm uppercase text-emerald-950">LEMBAR BUKU INDUK PESERTA DIDIK MI (EMIS 4.0)</h2>
                    <p class="text-[11px] font-semibold text-slate-700">${escapeHtml(profile.namaMadrasah)}</p>
                    <p class="text-[10px] text-slate-500">NSM: ${escapeHtml(profile.nsm)} | NPSN: ${escapeHtml(profile.npsn)} | T.P. ${escapeHtml(profile.tahunPelajaran)}</p>
                </div>
                <div class="w-[3cm] h-[4cm] border-2 border-slate-800 bg-white flex items-center justify-center overflow-hidden shrink-0">
                    ${s.foto ? `<img src="${escapeHtml(s.foto)}" class="w-full h-full object-cover">` : `<span class="text-[11px] font-bold text-slate-600">FOTO 3 x 4</span>`}
                </div>
            </div>
            <table class="w-full text-[10.5px] border-collapse border border-slate-400">
                <tbody class="divide-y divide-slate-300">
                    <tr class="bg-emerald-800 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">I. IDENTITAS DIRI SISWA</td></tr>
                    <tr><td class="w-56 px-2 py-0.5 text-slate-600 border-r border-slate-300">Nama Lengkap Siswa</td><td class="w-3 px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-bold uppercase">${escapeHtml(s.nama)}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Jenis Kelamin</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${s.jk === "P" ? "Perempuan" : "Laki-laki"}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">NISN</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono font-bold">${escapeHtml(s.nisn || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">NIK Siswa</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(maskLast10Digits(s.nik))}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">No. Kartu Keluarga</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(maskLast10Digits(s.noKK))}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">NIS Lokal Madrasah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(s.nisLokal || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Tempat, Tanggal Lahir</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.tempatLahir || "-")}, ${escapeHtml(s.tglLahir || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Agama / Kewarganegaraan</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.agama || "Islam")} / ${escapeHtml(s.kewarganegaraan || "WNI")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Anak Ke / Jumlah Saudara</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">Anak ke-${s.anakKe || 1} dari ${(s.jmlSaudara || 0) + 1} bersaudara</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Cita-cita / Hobi</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.citaCita || "-")} / ${escapeHtml(s.hobi || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nomor HP / WhatsApp</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(s.hpSiswa || "-")}</td></tr>

                    <tr class="bg-emerald-800 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">II. RIWAYAT MASUK, ROMBEL MI &amp; SEKOLAH ASAL</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Tingkat / Rombel Aktif</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-bold">Kelas ${escapeHtml(s.tingkat || "-")} / Rombel ${escapeHtml(s.rombel || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Rombel Awal Masuk</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.rombelMasuk || s.rombel || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Tanggal Masuk Madrasah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.tglMasuk || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Jenis Pendaftaran</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.jenisPendaftaran || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Asal RA / TK / PAUD</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.asalSekolah || "-")} (NPSN: ${escapeHtml(s.npsnAsal || "-")})</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">No. Surat Keterangan RA/TK</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(s.noIjazahAsal || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">No. Registrasi Akta Lahir</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(s.noUnSebelumnya || "-")}</td></tr>

                    <tr class="bg-emerald-800 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">III. TEMPAT TINGGAL, PESANTREN &amp; TRANSPORTASI</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Jenis Tempat Tinggal</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.tempatTinggal || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nama Pondok Pesantren / NSP</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${s.namaPondok ? `${escapeHtml(s.namaPondok)} (NSP: ${escapeHtml(s.nspPesantren || "-")})` : "-"}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Alamat Lengkap &amp; Kode Pos</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.alamatJalan || "-")}, RT ${escapeHtml(s.rt || "-")}/RW ${escapeHtml(s.rw || "-")}, Desa/Kel. ${escapeHtml(s.desa || "-")}, Kec. ${escapeHtml(s.kecamatan || "-")}, ${escapeHtml(s.kabupaten || "-")}, Prov. ${escapeHtml(s.provinsi || "-")} (${escapeHtml(s.kodePos || "-")})</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Transportasi / Jarak / Waktu</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.transportasi || "-")} / ${escapeHtml(s.jarak || "-")} (${escapeHtml(s.waktuTempuh || "-")})</td></tr>

                    <tr class="bg-emerald-800 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">IV. DATA ORANG TUA KANDUNG &amp; WALI</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nama Ayah Kandung</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-semibold">${escapeHtml(s.ayahNama || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">NIK / Tempat, Tgl Lahir Ayah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">NIK: ${escapeHtml(maskLast10Digits(s.ayahNik))} | TTL: ${escapeHtml(s.ayahTtl || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Pendidikan / Pekerjaan Ayah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.ayahPendidikan || "-")} / ${escapeHtml(s.ayahPekerjaan || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Penghasilan Bulanan Ayah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.ayahPenghasilan || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nama Ibu Kandung</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-semibold">${escapeHtml(s.ibuNama || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">NIK / Tempat, Tgl Lahir Ibu</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">NIK: ${escapeHtml(maskLast10Digits(s.ibuNik))} | TTL: ${escapeHtml(s.ibuTtl || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Pendidikan / Pekerjaan Ibu</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.ibuPendidikan || "-")} / ${escapeHtml(s.ibuPekerjaan || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Penghasilan Ibu / No. HP Ortu</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.ibuPenghasilan || "-")} | HP Ortu: ${escapeHtml(s.ortuHp || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nama Wali / Hubungan / NIK</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${s.waliNama ? `${escapeHtml(s.waliNama)} (${escapeHtml(s.waliHubungan || "Wali")}) | NIK: ${escapeHtml(maskLast10Digits(s.waliNik))}` : "-"}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Pekerjaan / Penghasilan / HP Wali</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${s.waliNama ? `${escapeHtml(s.waliPekerjaan || "-")} / ${escapeHtml(s.waliPenghasilan || "-")} / HP: ${escapeHtml(s.waliHp || "-")}` : "-"}</td></tr>

                    <tr class="bg-emerald-800 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">V. PROGRAM BANTUAN SOSIAL (PIP / PKH / KKS) &amp; KESEHATAN</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Status PIP / Nomor KIP</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${escapeHtml(s.statusPip || "Tidak")} (No. KIP: ${escapeHtml(s.noKip || "-")})</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">No. Rekening PIP &amp; Atas Nama</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">${s.rekPip ? `${escapeHtml(s.rekPip)} a.n. ${escapeHtml(s.namaRekPip || "-")}` : "-"}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Kartu PKH / Kartu KKS</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono">PKH: ${escapeHtml(s.statusPkh || "Tidak")} (${escapeHtml(s.noPkh || "-")}) | KKS: ${escapeHtml(s.statusKks || "Tidak")} (${escapeHtml(s.noKks || "-")})</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">TB / BB / Lingkar Kepala / Gol. Darah</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">TB: ${s.tb || "-"} cm, BB: ${s.bb || "-"} kg, LK: ${s.lingkarKepala || "-"} cm, Gol. Darah: ${escapeHtml(s.golDarah || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Kebutuhan Khusus / Disabilitas</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.disabilitas || "Tidak Ada")}</td></tr>

                    <tr class="bg-purple-900 text-white font-bold uppercase"><td colspan="3" class="px-2.5 py-1">VI. RIWAYAT KELUAR &amp; IJAZAH MADRASAH IBTIDAIYAH</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Status Siswa / Alasan Keluar</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-bold">${escapeHtml(s.statusSiswa || "Aktif")} (${escapeHtml(s.keluarDariMadrasah || "Masih Aktif")})</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Tanggal Keluar / Lulus</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.tglKeluar || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Nomor Seri Ijazah MI</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5 font-mono font-bold">${escapeHtml(s.noSeriIjazahMadrasah || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Sekolah / MTs Tujuan Lanjutan</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.sekolahTujuan || "-")}</td></tr>
                    <tr><td class="px-2 py-0.5 text-slate-600 border-r border-slate-300">Keterangan Tambahan</td><td class="px-1 py-0.5 text-center">:</td><td class="px-2 py-0.5">${escapeHtml(s.keteranganKeluar || "-")}</td></tr>
                </tbody>
            </table>
            <div class="flex justify-end pt-3 text-[11px]" style="font-family:'Times New Roman',serif;">
                <div class="w-60 leading-snug">
                    <div>${escapeHtml(profile.tempatSurat)}, ${new Date().toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" })}</div>
                    <div>Kepala Madrasah,</div>
                    <div class="h-14"></div>
                    <div class="font-bold underline">${escapeHtml(profile.kepalaNama)}</div>
                    <div>NIP. ${escapeHtml(profile.kepalaNip)}</div>
                </div>
            </div>
        </div>
    `;
}

export function buildTranskripRdmHtml(
    s: EmisStudent,
    rdmScores: RdmScoresMap,
    profile: EmisMadrasahProfile,
): string {
    const rec = rdmScores[String(s.id)] || {};
    const rows = RDM_SUBJECTS_MI.map((subj, idx) => {
        const vals = RDM_SEMESTERS.map((smt) => rec[subj]?.[String(smt)]);
        const nums = vals.filter((v): v is number => typeof v === "number");
        const avg = nums.length
            ? (nums.reduce((a, b) => a + b, 0) / nums.length).toFixed(1)
            : "-";
        return `<tr>
            <td style="border:1px solid #333;padding:3px;text-align:center;">${idx + 1}</td>
            <td style="border:1px solid #333;padding:3px;">${escapeHtml(subj)}</td>
            ${vals.map((v) => `<td style="border:1px solid #333;padding:3px;text-align:center;">${typeof v === "number" ? v.toFixed(1) : "-"}</td>`).join("")}
            <td style="border:1px solid #333;padding:3px;text-align:center;font-weight:bold;">${avg}</td>
        </tr>`;
    }).join("");

    return `
        <div class="page-break bg-white p-6 border border-slate-300 shadow" style="font-family:'Times New Roman',serif;font-size:10px;color:#000;">
            ${buildOfficialKopHtml(profile)}
            <div style="text-align:center;margin:10px 0;">
                <div style="font-weight:bold;font-size:13px;text-decoration:underline;">TRANSKRIP NILAI RAPOR DIGITAL MADRASAH (RDM) MI</div>
                <div>Semester 1 s.d. 12 (Kelas 1 - 6) | Tahun Pelajaran: ${escapeHtml(profile.tahunPelajaran)}</div>
            </div>
            <div style="margin-bottom:8px;font-size:11px;">
                <strong>Nama Siswa:</strong> ${escapeHtml(s.nama)} | <strong>NISN:</strong> ${escapeHtml(s.nisn || "-")} | <strong>Rombel:</strong> Kelas ${escapeHtml(s.rombel || "-")}
            </div>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="border:1px solid #333;padding:3px;">No</th>
                        <th style="border:1px solid #333;padding:3px;text-align:left;">Mata Pelajaran MI</th>
                        ${RDM_SEMESTERS.map((smt) => `<th style="border:1px solid #333;padding:3px;">S${smt}</th>`).join("")}
                        <th style="border:1px solid #333;padding:3px;">Rata2</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <div style="display:flex;justify-content:flex-end;margin-top:20px;font-size:11px;">
                <div style="width:220px;line-height:1.4;">
                    <div>${escapeHtml(profile.tempatSurat)}, ${new Date().toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" })}</div>
                    <div>Kepala Madrasah,</div>
                    <div style="height:48px;"></div>
                    <div style="font-weight:bold;text-decoration:underline;">${escapeHtml(profile.kepalaNama)}</div>
                    <div>NIP. ${escapeHtml(profile.kepalaNip)}</div>
                </div>
            </div>
        </div>
    `;
}

export function buildKtsCardHtml(
    s: EmisStudent,
    profile: EmisMadrasahProfile,
): string {
    return `
        <div class="border-2 border-emerald-800 rounded-xl overflow-hidden bg-white shadow text-slate-900">
            <div class="bg-emerald-800 text-white px-3 py-2 text-xs font-bold uppercase flex items-center gap-2">
                ${profile.logoKiri ? `<img src="${escapeHtml(profile.logoKiri)}" alt="Logo" class="w-5 h-5 object-contain bg-white rounded-xs p-0.5">` : `<span class="inline-block w-5 h-5 rounded bg-emerald-600 text-center leading-5 text-[10px]">MI</span>`}
                <span>KARTU TANDA SISWA — ${escapeHtml(profile.namaMadrasah)}</span>
            </div>
            <div class="p-3 flex items-center gap-3 text-xs">
                <div class="w-16 h-20 border border-slate-400 rounded overflow-hidden bg-slate-100 flex items-center justify-center shrink-0">
                    ${s.foto ? `<img src="${escapeHtml(s.foto)}" alt="Foto" class="w-full h-full object-cover">` : `<span class="text-[10px] font-bold text-slate-500">3x4</span>`}
                </div>
                <div class="min-w-0">
                    <div class="font-bold uppercase">${escapeHtml(s.nama)}</div>
                    <div class="font-mono text-[11px]">NISN: ${escapeHtml(s.nisn || "-")}</div>
                    <div class="text-[11px]">Kelas: <strong>${escapeHtml(s.rombel || "-")}</strong></div>
                    <div class="text-[11px]">TTL: ${escapeHtml(s.tempatLahir || "-")}, ${escapeHtml(s.tglLahir || "-")}</div>
                </div>
            </div>
        </div>
    `;
}

export function buildNominatifTableHtml(
    list: EmisStudent[],
    profile: EmisMadrasahProfile,
): string {
    return `
        <div class="bg-white p-6 rounded border border-slate-300 text-xs text-slate-900">
            ${buildOfficialKopHtml(profile)}
            <div class="font-bold text-center text-sm uppercase my-3">DAFTAR NOMINATIF PESERTA DIDIK — ${escapeHtml(profile.namaMadrasah)}</div>
            <table class="w-full border-collapse border border-slate-400">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border border-slate-400 p-1.5">No</th>
                        <th class="border border-slate-400 p-1.5">NISN</th>
                        <th class="border border-slate-400 p-1.5 text-left">Nama Lengkap</th>
                        <th class="border border-slate-400 p-1.5">L/P</th>
                        <th class="border border-slate-400 p-1.5">Rombel</th>
                        <th class="border border-slate-400 p-1.5">Tempat, Tgl Lahir</th>
                    </tr>
                </thead>
                <tbody>
                    ${list
                        .map(
                            (s, i) => `
                        <tr>
                            <td class="border border-slate-400 p-1.5 text-center">${i + 1}</td>
                            <td class="border border-slate-400 p-1.5 font-mono text-center">${escapeHtml(s.nisn || "-")}</td>
                            <td class="border border-slate-400 p-1.5 font-bold uppercase">${escapeHtml(s.nama)}</td>
                            <td class="border border-slate-400 p-1.5 text-center">${escapeHtml(s.jk || "-")}</td>
                            <td class="border border-slate-400 p-1.5 text-center">${escapeHtml(s.rombel || "-")}</td>
                            <td class="border border-slate-400 p-1.5">${escapeHtml(s.tempatLahir || "-")}, ${escapeHtml(s.tglLahir || "-")}</td>
                        </tr>`,
                        )
                        .join("")}
                </tbody>
            </table>
        </div>
    `;
}

export function openPrintWindowWithContent(
    innerHtml: string,
    title: string,
): void {
    const win = window.open("", "_blank");
    if (!win) {
        window.print();
        return;
    }
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${escapeHtml(title)}</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <style>
                @media print { .page-break { page-break-after: always; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
                body { font-family: Arial, sans-serif; color: #000; }
            </style>
        </head>
        <body class="p-4">${innerHtml}</body>
        </html>
    `);
    win.document.close();
    setTimeout(() => {
        win.focus();
        win.print();
    }, 600);
}
