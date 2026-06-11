/**
 * Cloudflare Pages Function — odoslanie registrácie do Ecomailu
 * Cesta: /api/subscribe
 * Nastav v Cloudflare premenné: ECOMAIL_API_KEY (Secret), ECOMAIL_LIST_ID, ECOMAIL_TAG (voliteľné)
 */

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export async function onRequestPost(context) {
  const { request, env } = context;

  try {
    const data = await request.json();
    const name  = String(data.name  || "").trim();
    const email = String(data.email || "").trim();
    const phone = String(data.phone || "").trim();

    if (name.length < 2)      return json({ ok: false, error: "invalid_name" }, 400);
    if (!EMAIL_RE.test(email)) return json({ ok: false, error: "invalid_email" }, 400);

    const parts     = name.split(/\s+/);
    const firstName = parts.shift();
    const surname   = parts.join(" ");

    const subscriber_data = {
      name:    firstName,
      surname: surname,
      email:   email,
      tags:    [env.ECOMAIL_TAG || "webinar-investovanie-tower-finance"],
    };
    if (phone) subscriber_data.phone = phone;

    const res = await fetch(
      `https://api2.ecomailapp.cz/lists/${env.ECOMAIL_LIST_ID}/subscribe`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "key": env.ECOMAIL_API_KEY,
        },
        body: JSON.stringify({
          subscriber_data,
          trigger_autoresponders: true,
          update_existing: true,
          resubscribe: false,
        }),
      }
    );

    if (!res.ok) {
      const detail = await res.text();
      return json({ ok: false, error: "ecomail_error", status: res.status, detail }, 502);
    }

    return json({ ok: true });
  } catch (e) {
    return json({ ok: false, error: "server_error", detail: String(e) }, 500);
  }
}

function json(obj, status = 200) {
  return new Response(JSON.stringify(obj), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}
