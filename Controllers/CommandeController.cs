using Microsoft.AspNetCore.Mvc;
using BrasilBurger.Data;
using BrasilBurger.Models;
using BrasilBurger.Helpers;
using Microsoft.EntityFrameworkCore;
using System;
using System.Linq;
using System.Collections.Generic;
using Microsoft.AspNetCore.Http;



namespace BrasilBurger.Controllers
{
    public class CommandeController : Controller
    {
        private ApplicationDbContext _context;

        public CommandeController(ApplicationDbContext context)
        {
            _context = context;
        }

        
        public IActionResult ChoisirModeLivraison()
        {
           
            var clientId = HttpContext.Session.GetClientId();
            if (clientId == null)
            {
                TempData["Error"] = "Veuillez vous connecter";
                return RedirectToAction("Connexion", "Auth");
            }

     
            var panier = HttpContext.Session.GetPanier();
            if (panier.Articles.Count == 0)
            {
                TempData["Error"] = "Votre panier est vide";
                return RedirectToAction("Index", "Panier");
            }

         
            var zones = _context.Zones.ToList();
            var zonesActives = new List<Zone>();
            foreach (var z in zones)
            {
                if (z.EstArchive == false)
                {
                    zonesActives.Add(z);
                }
            }

            ViewBag.Zones = zonesActives;
            ViewBag.Panier = panier;

            return View();
        }

       
        [HttpPost]
        public IActionResult PasserCommande(string typeConsommation, int? zoneId)
        {
            var clientId = HttpContext.Session.GetClientId();
            if (clientId == null)
            {
                return RedirectToAction("Connexion", "Auth");
            }

            var panier = HttpContext.Session.GetPanier();
            if (panier.Articles.Count == 0)
            {
                return RedirectToAction("Index", "Panier");
            }

            
            decimal montantTotal = panier.Total;
            
          
            if (typeConsommation == "LIVRAISON" && zoneId != null)
            {
                var zone = _context.Zones.Find(zoneId);
                if (zone != null)
                {
                    montantTotal = montantTotal + zone.Prix;
                }
            }

           
            var commande = new Commande();
            commande.ClientId = clientId.Value;
            commande.TypeConsommation = typeConsommation ?? "SUR_PLACE";
            commande.MontantTotal = montantTotal;
            commande.Statut = "EN_COURS";
            commande.DateCommande = DateTime.UtcNow;
            
            if (typeConsommation == "LIVRAISON")
            {
                commande.ZoneId = zoneId;
            }

            _context.Commandes.Add(commande);
            _context.SaveChanges();

            
            foreach (var article in panier.Articles)
            {
                var ligne = new LigneCommande();
                ligne.CommandeId = commande.Id;
                ligne.Quantite = article.Quantite;
                ligne.PrixUnitaire = article.Prix;
                ligne.MontantTotal = article.Total;
                ligne.TypeLigne = article.Type ?? "BURGER";

                if (article.Type == "MENU")
                {
                    ligne.MenuId = article.Id;
                }
                else
                {
                    ligne.ProduitId = article.Id;
                }

                _context.LignesCommande.Add(ligne);
            }
            _context.SaveChanges();

           
            HttpContext.Session.SetInt32("CommandeEnCours", commande.Id);

            return RedirectToAction("Payer");
        }

     
        public IActionResult Payer()
        {
            var commandeId = HttpContext.Session.GetInt32("CommandeEnCours");
            if (commandeId == null)
            {
                return RedirectToAction("Index", "Home");
            }

            var commande = _context.Commandes
                .Include(c => c.Zone)
                .ToList();

            Commande? commandeTrouvee = null;
            foreach (var c in commande)
            {
                if (c.Id == commandeId)
                {
                    commandeTrouvee = c;
                    break;
                }
            }

            if (commandeTrouvee == null)
            {
                return NotFound();
            }

            return View(commandeTrouvee);
        }

        
        [HttpPost]
        public IActionResult PayerWave()
        {
            var commandeId = HttpContext.Session.GetInt32("CommandeEnCours");
            if (commandeId == null)
            {
                return RedirectToAction("Index", "Home");
            }

            var commande = _context.Commandes.Find(commandeId);
            if (commande == null)
            {
                return NotFound();
            }

           
            var paiement = new Paiement();
            paiement.CommandeId = commande.Id;
            paiement.Montant = commande.MontantTotal;
            paiement.TypePaiement = "WAVE";
            paiement.DatePaiement = DateTime.UtcNow;

            _context.Paiements.Add(paiement);

            
            commande.Statut = "VALIDEE";
            _context.SaveChanges();

         
            HttpContext.Session.SetPanier(new Panier());
            HttpContext.Session.Remove("CommandeEnCours");

            return RedirectToAction("Confirmation", new { id = commande.Id });
        }

        
        [HttpPost]
        public IActionResult PayerOM()
        {
            var commandeId = HttpContext.Session.GetInt32("CommandeEnCours");
            if (commandeId == null)
            {
                return RedirectToAction("Index", "Home");
            }

            var commande = _context.Commandes.Find(commandeId);
            if (commande == null)
            {
                return NotFound();
            }

         
            var paiement = new Paiement();
            paiement.CommandeId = commande.Id;
            paiement.Montant = commande.MontantTotal;
            paiement.TypePaiement = "OM";
            paiement.DatePaiement = DateTime.UtcNow;

            _context.Paiements.Add(paiement);

           
            commande.Statut = "VALIDEE";
            _context.SaveChanges();

            
            HttpContext.Session.SetPanier(new Panier());
            HttpContext.Session.Remove("CommandeEnCours");

            return RedirectToAction("Confirmation", new { id = commande.Id });
        }

     
        public IActionResult Confirmation(int id)
        {
            var commandes = _context.Commandes
                .Include(c => c.LignesCommande)
                .Include(c => c.Zone)
                .Include(c => c.Paiement)
                .ToList();

            Commande? commande = null;
            foreach (var c in commandes)
            {
                if (c.Id == id)
                {
                    commande = c;
                    break;
                }
            }

            if (commande == null)
            {
                return NotFound();
            }

            return View(commande);
        }

      
        public IActionResult MesCommandes()
        {
            var clientId = HttpContext.Session.GetClientId();
            if (clientId == null)
            {
                return RedirectToAction("Connexion", "Auth");
            }

            var commandes = _context.Commandes
                .Include(c => c.LignesCommande)
                .Include(c => c.Paiement)
                .ToList();

            
            var mesCommandes = new List<Commande>();
            foreach (var c in commandes)
            {
                if (c.ClientId == clientId)
                {
                    mesCommandes.Add(c);
                }
            }

            return View(mesCommandes);
        }

   
        public IActionResult Detail(int id)
        {
            var clientId = HttpContext.Session.GetClientId();
            if (clientId == null)
            {
                return RedirectToAction("Connexion", "Auth");
            }

            var commandes = _context.Commandes
                .Include(c => c.LignesCommande)
                    .ThenInclude(l => l.Produit)
                .Include(c => c.LignesCommande)
                    .ThenInclude(l => l.Menu)
                .Include(c => c.Zone)
                .Include(c => c.Livreur)
                .Include(c => c.Paiement)
                .ToList();

            Commande? commande = null;
            foreach (var c in commandes)
            {
                if (c.Id == id && c.ClientId == clientId)
                {
                    commande = c;
                    break;
                }
            }

            if (commande == null)
            {
                return NotFound();
            }

            return View(commande);
        }
    }
}