using Microsoft.AspNetCore.Mvc;
using BrasilBurger.Data;
using Microsoft.EntityFrameworkCore;
using System.Collections.Generic;
using System.Linq;

namespace BrasilBurger.Controllers
{
    public class HomeController : Controller
    {
        private ApplicationDbContext _context;

        public HomeController(ApplicationDbContext context)
        {
            _context = context;
        }

       
        public IActionResult Index()
        {
            
            var produits = _context.Produits.ToList();

            
            var burgers = new List<BrasilBurger.Models.Produit>();
            int count = 0;
            foreach (var p in produits)
            {
                if (p.TypeProduit == "BURGER" && p.EstArchive == false)
                {
                    burgers.Add(p);
                    count++;
                    if (count >= 4) break;
                }
            }

         
            var tousLesMenus = _context.Menus
                .Include(m => m.Burger)
                .Include(m => m.Boisson)
                .Include(m => m.Frite)
                .ToList();

            
            var menus = new List<BrasilBurger.Models.Menu>();
            count = 0;
            foreach (var m in tousLesMenus)
            {
                if (m.EstArchive == false)
                {
                    menus.Add(m);
                    count++;
                    if (count >= 4) break;
                }
            }

            ViewBag.Burgers = burgers;
            ViewBag.Menus = menus;

            return View();
        }
    }
}