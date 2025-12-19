using System.Collections.Generic;

namespace BrasilBurger.Models
{
    public class ArticlePanier
    {
        public int Id { get; set; }
        public string Nom { get; set; } = "";
        public decimal Prix { get; set; }
        public string? Image { get; set; }
        public string Type { get; set; } = "";
        public int Quantite { get; set; } = 1;

        public decimal Total
        {
            get
            {
                return Prix * Quantite;
            }
        }
    }

    public class Panier
    {
        public List<ArticlePanier> Articles { get; set; } = new List<ArticlePanier>();

        public decimal Total
        {
            get
            {
                decimal total = 0;
                foreach (var article in Articles)
                {
                    total += article.Total;
                }
                return total;
            }
        }

        public int NombreArticles
        {
            get
            {
                int count = 0;
                foreach (var article in Articles)
                {
                    count += article.Quantite;
                }
                return count;
            }
        }

        public void AjouterArticle(int id, string nom, decimal prix, string? image, string type)
        {
            ArticlePanier? articleExistant = null;
            foreach (var article in Articles)
            {
                if (article.Id == id && article.Type == type)
                {
                    articleExistant = article;
                    break;
                }
            }

            if (articleExistant != null)
            {
                articleExistant.Quantite++;
            }
            else
            {
                var nouveauArticle = new ArticlePanier();
                nouveauArticle.Id = id;
                nouveauArticle.Nom = nom;
                nouveauArticle.Prix = prix;
                nouveauArticle.Image = image;
                nouveauArticle.Type = type;
                nouveauArticle.Quantite = 1;
                Articles.Add(nouveauArticle);
            }
        }

        public void SupprimerArticle(int id)
        {
            ArticlePanier? articleASupprimer = null;
            foreach (var article in Articles)
            {
                if (article.Id == id)
                {
                    articleASupprimer = article;
                    break;
                }
            }

            if (articleASupprimer != null)
            {
                Articles.Remove(articleASupprimer);
            }
        }
    }
}