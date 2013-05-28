#install.packages("reshape2")
library("reshape2")
#install.packages("sqldf")
library("sqldf")

path = _DBanalysis/
dataname = cz_praha_2010-2013
analysisname = cz_praha_2010-2013_1q

#lower limit to eliminate from calculations
lo_limit = .1
#lower limit to eliminate from projections
lo_limitT = lo_limit

#raw data in 3 columns (values in [-1,1])
Graw = read.table(paste(path,dataname,"/",dataname,".csv",sep=""), header=FALSE, sep=",")
#data divisions x persons
G = acast(Graw,V2~V1,value.var='V3')
#scaled divisions x persons (mean=0 and sd=1 for each division)
H=t(scale(t(G),scale=TRUE))

#weights
#weights 1, based on number of persons in division
w1 = apply(abs(G)==1,1,sum,na.rm=TRUE)/max(apply(abs(G)==1,1,sum,na.rm=TRUE))
w1[is.na(w1)] = 0
#weights 2, "100:100" vs. "195:5"
w2 = 1 - abs(apply(G==1,1,sum,na.rm=TRUE) - apply(G==-1,1,sum,na.rm=TRUE))/apply(!is.na(G),1,sum) 
w2[is.na(w2)] = 0

	#analytics
	#plot(w1)
	#plot(w2)
	#plot(w1*w2)

#weighted scaled divisions x persons
Hw = H * w1 * w2

#index of missing data
#index of missing data divisions x persons
HI = H
HI[!is.na(H)]=1
HI[is.na(H)] = 0

#weighted scaled with NA->0 division x persons
Hw0 = Hw
Hw0[is.na(Hw)]=0

#eliminate persons with too few votes (weighted)

#weights for non missing data division x persons
HIw = HI*w1*w2
#sum of weights of divisions for each persons
tmp = apply(HIw,2,sum)
pw = tmp/max(tmp)
#index of persons in calculation
pI = pw > lo_limit
#weighted scaled with NA->0 and cutted persons with too few votes division x persons
Hw0c = Hw0[,pI]
#index of missing data cutted persons with too few votes divisions x persons
HIc = HI[,pI]

#"covariance" matrix adjusted according to missing data
Hcov=t(Hw0c)%*%Hw0c * 1/(t(HIc)%*%HIc) * (dim(Hw0c)[1])
#Hcov=t(Hw0)%*%Hw0 * 1/(t(HI)%*%HI-1) * (dim(H)[1]-1)

#substitution of missing data in "covariance" matrix
Hcov0 = Hcov
Hcov0[is.na(Hcov)] = 0			#********* 

#eigendecomposition
He=eigen(Hcov0)
# V (rotation values of persons)
V = He$vectors
#projected divisions into dimensions
Hy=Hw0c%*%V

	#analytics
	#plot(Hy[,1],Hy[,2])
	#plot(sqrt(He$values[1:10]))

#sigma matrix 
sigma = diag(sqrt(He$values))
sigma[is.na(sigma)] = 0

#projection of persons into dimensions
Hproj = t(sigma%*%t(V))
	#analytics
	#plot(Hproj[,1],Hproj[,2])
	
	#second projection
    #Hproj2 = t(t(U) %*% Hw0c)
    	#without missing values should be equal
    #plot(Hproj[,1],Hproj[,1])
    #plot(Hproj[,2],Hproj[,2])

#sigma^-1 matrix
sigma_1 = diag(sqrt(1/He$values))
sigma_1[is.na(sigma_1)] = 0

# U (rotation values of divisions)
U = Hw0c%*%V%*%sigma_1

#U%*%sigma%*%t(V) != Hw0c ;because of adjusting of "covariance" matrix


# NEW MP (or partial)
#New persons / partial (like a projection of divisions from a smaller time interval into all divisions)


analdb = dbConnect(SQLite(), dbname=paste(path,dataname,"/",analysisname,"/",analysisname,".sqlite3",sep=""))
datadb = dbConnect(SQLite(), dbname=paste(path,dataname,"/",dataname,".sqlite3",sep=""))

rotation = strsplit(dbGetQuery(analdb,"SELECT orientation FROM analysis_info")[1,1],",")[[1]]
for (j in 2:length(rotation)) {
  if (Hproj[rot_mp_rank,j-1] * as.double(rotation[j]) < 0) {
    U[,j-1] = -1*U[,j-1]
  }
}

aU = abs(U)

mp_names = dbGetQuery(datadb,"SELECT name FROM mp ORDER BY CAST(code as INTEGER)")
attr(G,'dimnames')[2][[1]] = mp_names$name
#attr(Hw0c,'dimnames')[2][[1]] = mp_names$name[pI]

interval_names = dbGetQuery(analdb,"SELECT distinct(interval_name) FROM analysis_division_in_interval ORDER BY interval_name")

    


for (i in 1:dim(interval_names)[1]) {

    TIf = dbGetQuery(analdb,paste("SELECT division_code, CASE interval_name='",interval_names[i,],"' WHEN 1 THEN 'TRUE' ELSE 'FALSE' END as in_interval FROM analysis_division_in_interval ORDER BY division_code",sep=""))
    
    TIf$in_interval = as.logical(TIf$in_interval)
    
    max_division_code = TIf$division_code[max(which(as.logical(TIf$in_interval)))]
    min_division_code = TIf$division_code[min(which(as.logical(TIf$in_interval)))]
    
    TI = TIf$in_interval
    
    GTc = G[,pI]
    GTc[!TI,] = NA
    HTc = (GTc - attr(H,"scaled:center"))/attr(H,"scaled:scale")
    
    HTIc = HTc
	HTIc[!is.na(HTIc)] = 1
	HTIc[is.na(HTIc)] = 0
    
    HTw0c = HTc * w1 * w2
    HTw0c[is.na(HTw0c)] = 0
    

	#weights for non missing data division x persons
	HTIcw = HTIc*w1*w2
	#sum of weights of divisions for each persons
	tmp = apply(HTIcw,2,sum)
	pTw = tmp/max(tmp)
	#index of persons in calculation
	pTI = pTw > lo_limitT
	
	#weighted scaled with NA->0 and cutted persons with too few votes division x persons
	HTw0cc = HTw0c[,pTI]
	#index of missing data cutted persons with too few votes divisions x persons
	HTIcc = HTIc[,pTI]
    
    
	dweights = t(t(aU)%*%HTIcc / apply(aU,2,sum))  	#person x division
    dweights[is.na(dweights)] = 0
    
    HTw0ccproj = t(HTw0cc)%*%U / dweights


    parties = as.matrix(dbGetQuery(datadb,paste('SELECT COALESCE(NULLIF(tmax.code,""),tmin.code) as code, COALESCE(NULLIF(tmax.color,""),tmin.color) as color FROM (SELECT  mvf.mp_code, g.code, g.color FROM mp_vote_full as mvf  LEFT JOIN "group" as g  ON mvf.group_code=g.code  WHERE division_code="',max_division_code,'") as tmax LEFT JOIN  (SELECT  mvf.mp_code, g.code, g.color FROM mp_vote_full as mvf  LEFT JOIN "group" as g  ON mvf.group_code=g.code  WHERE division_code="',min_division_code,'") as tmin ON tmax.mp_code=tmin.mp_code  ORDER BY CAST(tmax.mp_code as INTEGER)',sep="")))
    partiescc = (parties[pI,])[pTI,]
    
    write.csv(format(cbind(HTw0ccproj[,1:2],partiescc),digits=3),file=paste(path,dataname,"/",analysisname,"/",interval_names[i,],"_result.csv",sep=""))
     #*************************
}
