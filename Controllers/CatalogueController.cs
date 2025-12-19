using Microsoft.AspNetCore.Mvc;
using BrasilBurger.Data;
using Microsoft.EntityFrameworkCore;
using System.Collections.Generic;
using System.Linq;

namespace BrasilBurger.Controllers
{
    public class CatalogueController : Controller
    {
        private ApplicationDbContext _context;

        public CatalogueController(ApplicationDbContext context)
        {
            _context = context;
        }

      
        public IActionResult Index(string filter)
        {
          
            var produits = _context.Produits.ToList();

        
            var burgers = new List<BrasilBurger.Models.Produit>();
            foreach (var p in produits)
            {
                if (p.TypeProduit == "BURGER" && p.EstArchive == false)
                {
                    burgers.Add(p);
                }
            }

       
            var frites = new List<BrasilBurger.Models.Produit>();
            foreach (var p in produits)
            {
                if (p.TypeComplement == "FRITE" && p.EstArchive == false)
                {
                    frites.Add(p);
                }
            }

            
            var boissons = new List<BrasilBurger.Models.Produit>();
            foreach (var p in produits)
            {
                if (p.TypeComplement == "BOISSON" && p.EstArchive == false)
                {
                    boissons.Add(p);
                }
            }

            ViewBag.Burgers = burgers;
            ViewBag.Frites = frites;
            ViewBag.Boissons = boissons;
            ViewBag.Filter = filter;

            return View();
        }

       
        public IActionResult Burgers()
        {
            var produits = _context.Produits.ToList();
            
            var burgers = new List<BrasilBurger.Models.Produit>();
            foreach (var p in produits)
            {
                if (p.TypeProduit == "BURGER" && p.EstArchive == false)
                {
                    burgers.Add(p);
                }
            }

            return View(burgers);
        }

       
        public IActionResult DetailBurger(int id)
        {
            var burger = _context.Produits.Find(id);
            
            if (burger == null)
            {
                return NotFound();
            }

            return View(burger);
        }

      
        public IActionResult Menus()
        {
            var menus = _context.Menus
                .Include(m => m.Burger)
                .Include(m => m.Frite)
                .Include(m => m.Boisson)
                .ToList();

         
            var menusActifs = new List<BrasilBurger.Models.Menu>();
            foreach (var m in menus)
            {
                if (m.EstArchive == false)
                {
                    menusActifs.Add(m);
                }
            }

            return View(menusActifs);
        }

      
        public IActionResult DetailMenu(int id)
        {
            var menus = _context.Menus
                .Include(m => m.Burger)
                .Include(m => m.Frite)
                .Include(m => m.Boisson)
                .ToList();

           
            BrasilBurger.Models.Menu? menu = null;
            foreach (var m in menus)
            {
                if (m.Id == id)
                {
                    menu = m;
                    break;
                }
            }

            if (menu == null)
            {
                return NotFound();
            }

            return View(menu);
        }
    }
}