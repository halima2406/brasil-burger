using Microsoft.AspNetCore.Http;
using System.Text.Json;
using BrasilBurger.Models;

namespace BrasilBurger.Helpers
{
    public static class SessionHelper
    {
        public static void SetPanier(this ISession session, Panier panier)
        {
            string json = JsonSerializer.Serialize(panier);
            session.SetString("Panier", json);
        }

        public static Panier GetPanier(this ISession session)
        {
            string? json = session.GetString("Panier");
            if (json == null)
            {
                return new Panier();
            }
            return JsonSerializer.Deserialize<Panier>(json) ?? new Panier();
        }

        public static void SetClientId(this ISession session, int id)
        {
            session.SetInt32("ClientId", id);
        }

        public static int? GetClientId(this ISession session)
        {
            return session.GetInt32("ClientId");
        }
    }
}