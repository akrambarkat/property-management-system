function n(t,o){const m={ILS:"₪",JOD:"د.أ",USD:"$"}[o]||"₪";return`${Number(t).toLocaleString("ar-SA",{minimumFractionDigits:2,maximumFractionDigits:2})} ${m}`}export{n as f};
