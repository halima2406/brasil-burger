using Microsoft.AspNetCore.Mvc;
using BrasilBurger.Data;
using BrasilBurger.Models;
using BrasilBurger.Helpers;

namespace BrasilBurger.Controllers
{
    public class PanierController : Controller
    {
        private ApplicationDbContext _context;

        public PanierController(ApplicationDbContext context)
        {
            _context = context;
        }

       
        public IActionResult Index()
        {
            var panier = HttpContext.Session.GetPanier();
            return View(panier);
        }

        
        public IActionResult AjouterProduit(int id, string type)
        {
            var panier = HttpContext.Session.GetPanier();

            if (type == "MENU")
            {
                var menu = _context.Menus.Find(id);
                if (menu != null)
                {
                    panier.AjouterArticle(menu.Id, menu.Nom, menu.Prix, menu.Image, "MENU");
                }
            }
            else
            {
                var produit = _context.Produits.Find(id);
                if (produit != null)
                {
                    panier.AjouterArticle(produit.Id, produit.Nom, produit.Prix, produit.Image, produit.TypeProduit);
                }
            }

            HttpContext.Session.SetPanier(panier);

           
            string referer = Request.Headers["Referer"].ToString();
            if (referer != "")
            {
                return Redirect(referer);
            }
            return RedirectToAction("Index", "Catalogue");
        }

     
        public IActionResult AjouterBurger(int id)
        {
            var produit = _context.Produits.Find(id);
            
            if (produit != null)
            {
                var panier = HttpContext.Session.GetPanier();
                panier.AjouterArticle(produit.Id, produit.Nom, produit.Prix, produit.Image, "BURGER");
                HttpContext.Session.SetPanier(panier);
            }

            return RedirectToAction("Index", "Catalogue");
        }

        
        public IActionResult AjouterMenu(int id)
        {
            var menu = _context.Menus.Find(id);
            
            if (menu != null)
            {
                var panier = HttpContext.Session.GetPanier();
                panier.AjouterArticle(menu.Id, menu.Nom, menu.Prix, menu.Image, "MENU");
                HttpContext.Session.SetPanier(panier);
            }

            return RedirectToAction("Menus", "Catalogue");
        }

      
        public IActionResult AjouterComplement(int id)
        {
            var produit = _context.Produits.Find(id);
            
            if (produit != null)
            {
                var panier = HttpContext.Session.GetPanier();
                string type = "COMPLEMENT";
                if (produit.TypeComplement != null)
                {
                    type = produit.TypeComplement;
                }
                panier.AjouterArticle(produit.Id, produit.Nom, produit.Prix, produit.Image, type);
                HttpContext.Session.SetPanier(panier);
            }

            return RedirectToAction("Index", "Catalogue");
        }

        
        public IActionResult Supprimer(int id)
        {
            var panier = HttpContext.Session.GetPanier();
            panier.SupprimerArticle(id);
            HttpContext.Session.SetPanier(panier);
            return RedirectToAction("Index");
        }

       
        public IActionResult Augmenter(int id)
        {
            var panier = HttpContext.Session.GetPanier();
            
            foreach (var article in panier.Articles)
            {
                if (article.Id == id)
                {
                    article.Quantite = article.Quantite + 1;
                    break;
                }
            }
            
            HttpContext.Session.SetPanier(panier);
            return RedirectToAction("Index");
        }

        
        public IActionResult Diminuer(int id)
        {
            var panier = HttpContext.Session.GetPanier();
            
            foreach (var article in panier.Articles)
            {
                if (article.Id == id)
                {
                    if (article.Quantite > 1)
                    {
                        article.Quantite = article.Quantite - 1;
                    }
                    else
                    {
                        panier.SupprimerArticle(id);
                    }
                    break;
                }
            }
            
            HttpContext.Session.SetPanier(panier);
            return RedirectToAction("Index");
        }

      
        public IActionResult Vider()
        {
            HttpContext.Session.SetPanier(new Panier());
            return RedirectToAction("Index");
        }
    }
}